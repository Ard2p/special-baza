<?php

namespace Modules\Dispatcher\Http\Controllers;

use App\Directories\LeadRejectReason;
use App\Directories\Unit;
use App\Helpers\RequestHelper;
use App\Machinery;
use App\Machines\Brand;
use App\Machines\Type;
use App\Service\Google\CalendarService;
use App\Service\OrderService;
use App\Service\RequestBranch;
use App\Service\Subscription;
use App\Support\Region;
use App\User;
use App\User\IndividualRequisite;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Jurosh\PDFMerge\PDFMerger;
use Lang;
use Modules\AdminOffice\Entities\Filter;
use Modules\CompanyOffice\Entities\Company\CompanyBranch;
use Modules\CompanyOffice\Entities\Company\GoogleCalendar;
use Modules\CompanyOffice\Services\CompanyRoles;
use Modules\ContractorOffice\Entities\Vehicle\Price;
use Modules\Dispatcher\Entities\Customer;
use Modules\Dispatcher\Entities\Directories\Vehicle;
use Modules\Dispatcher\Entities\DispatcherInvoice;
use Modules\Dispatcher\Entities\DispatcherInvoiceLeadPivot;
use Modules\Dispatcher\Entities\Lead;
use Modules\Dispatcher\Entities\LeadOffer;
use Modules\Dispatcher\Entities\LeadOfferPosition;
use Modules\Dispatcher\Http\Requests\AcceptDispatcherOffer;
use Modules\Dispatcher\Http\Requests\AcceptOffer;
use Modules\Dispatcher\Http\Requests\CreateLeadRequest;
use Modules\Dispatcher\Http\Requests\CreateMyOrderFromLead;
use Modules\Dispatcher\Http\Requests\CreateOffer;
use Modules\Dispatcher\Http\Requests\SelectContrator;
use Modules\Dispatcher\Services\CommercialOfferService;
use Modules\Dispatcher\Services\DocumentService;
use Modules\Dispatcher\Services\LeadService;
use Modules\Dispatcher\Transformers\Lead\ClientView;
use Modules\Dispatcher\Transformers\Lead\ContractorView;
use Modules\Dispatcher\Transformers\Lead\DispatcherView;
use Modules\Dispatcher\Transformers\LeadList;
use Modules\Integrations\Entities\Amo\AmoLead;
use Modules\Integrations\Services\OneC\OneCService;
use Modules\Orders\Entities\Order;
use Modules\Orders\Entities\OrderDocument;

class LeadsController extends Controller
{

    /** @var CompanyBranch */
    private $companyBranch;

    public function __construct(
        Request       $request,
        RequestBranch $companyBranch)
    {

            $this->companyBranch = $companyBranch->companyBranch;
            if ($request->filled('phone')) {
                $request->merge([
                    'phone' => trimPhone($request->phone)
                ]);
            }

            $block = $this->companyBranch->getBlockName(CompanyRoles::BRANCH_PROPOSALS);
            $this->middleware("accessCheck:{$block}," . CompanyRoles::ACTION_SHOW)->only([
                'index', 'getCommercialOffer', 'createHelper', 'getDocuments'
            ]);
            $this->middleware("accessCheck:{$block}," . CompanyRoles::ACTION_CREATE)->only([
                'store', 'update',
                'changeManager',
                'closeLead',
                'selectContractor',
                'createOrder',
                'updateName',
                'acceptDispatcherOffer',
                'acceptOffer',
                'mergePdf',
                'rejectReasons'
            ]);
            $this->middleware("accessCheck:{$block}," . CompanyRoles::ACTION_DELETE)->only(['destroy', 'closeLead']);

    }

    /**
     * Display a listing of the resource.
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function index(Request $request)
    {
        $request->validate([
            'type' => 'in:owner,transbaza,dispatcher'
        ]);

        /** @var Builder $leads */
        $leads = Lead::query()->forDomain()->with('region', 'city', 'customer', 'positions', 'rejectType', 'manager',
            'positions.model',
            'positions.category.optional_attributes',
        )->where('is_fast_order', 0);

        $filter = new Filter($leads);

        $filter->getLike([
            'customer_name' => 'customer_name',
            'address'       => 'address',
            'id'            => 'internal_number',
            'comment'       => 'comment',
            'created_at'    => 'created_at',
        ])->getEqual([
            'rent_type'  => 'tmp_status',
            'creator_id' => 'creator_id',
        ])
            ->getDateBetween([
                'date_from' => 'start_date'
            ]);

        $leads->where(function ($leads) use
        (
            $request
        ) {
            $filter = new Filter($leads);

            $filter->getLike([
                'phone'          => 'phone',
                'comment'          => 'comment',
                'contact_person' => 'customer_name',
            ]);
            if ($request->anyFilled(['phone', 'contact_person'])) {
                $leads->orWhereHas('contacts', function ($customers) {
                    $filter = new Filter($customers);
                    $customers->whereHas('phones', function ($q) {
                        $filter = new Filter($q);
                        $filter->getLike([
                            'phone' => 'phone',
                        ]);
                    });
                    $filter->getLike([
                        'phone'          => 'phone',
                        'contact_person' => 'contact_person',
                    ]);
                });
            }
        });

        if (toBool($request->tomorrow)) {
            $leads->startTomorrow();
        }
        if (toBool($request->accepted)) {
            $leads->where('accepted', 1);
        }

        $leads->where(function ($query) {
            $query->where('source', '!=', 'avito')
                ->orWhereNull('source');
        });
        if (toBool($request->tender)) {
            $leads->where('tender', 1);
        }
        if ($request->filled('category_id')) {
            $leads->whereRelation('positions', 'type_id', $request->category_id);
        }
        if ($request->first_date_rent) {
            $leads->whereRelation('positions', 'date_from', 'like', "%{$request->first_date_rent}%");
        }
        switch ($request->input('type')) {

            case 'owner':
                $leads->forBranch()->clientLead();
                // if ($request->filled('archive')) {
                //     $leads->archive();
                // } else {
                //     $leads->active();
                // }
                break;
            case 'transbaza':

                $leads->withoutCompany()->visible();

                if ($request->filled('archive')) {
                    $leads->archive();
                } elseif ($request->input('status') !== Lead::STATUS_OPEN) {
                    $leads->whereHas('orders', function ($q) {
                        $q->where('contractor_id', $this->companyBranch->id);
                    });
                }
                break;

            case 'dispatcher':

                $leads->forBranch()->dispatcherLead();

                // if ($request->filled('archive')) {
//
                //     $leads->archive();
                // } else {
                //     $leads->active();
                // }
                break;
            default:
                $leads->forBranch();
                break;
        }

        if ($request->has('ids')) {
            $leads->whereIn('id', explode(',', $request->input('ids')));
            return LeadList::collection($leads->get());
        }

        if ($request->filled('customer_id')) {
            $leads->where('customer_type', Customer::class)
                ->where('customer_id', $request->input('customer_id'));
        }

        if ($request->filled('sortBy')) {
            $sort = $request->input('sortBy');
            $type =
                toBool($request->input('asc'))
                    ? 'asc'
                    : 'desc';

            switch ($sort) {
                case 'start_date':
                    $leads->orderBy('start_date', $type);
                    break;
                case 'end_date':
                    $leads->withCount([
                        'positions as end_date' => function ($query) {
                            $query->select(DB::raw("
                        CASE  
                        WHEN lead_positions.order_type = 'shift'
                        THEN DATE_ADD(lead_positions.date_from, INTERVAL lead_positions.order_duration DAY)
                         WHEN lead_positions.order_type ='hour'
                        THEN DATE_ADD(lead_positions.date_from, INTERVAL lead_positions.order_duration HOUR)
                        END
                        "));
                        }
                    ]);

                    $leads->orderBy('end_date', 'desc');
                    break;
            }
        } else {
            $leads->orderBy('created_at', 'desc');
        }
        //logger()->debug($leads->toSql(),$leads->getBindings());

        return LeadList::collection($leads->paginate($request->per_page
            ?: 10));
    }


    function generateInvoice(
        Request $request,
                $id
    )
    {
        $request->validate([
            '*.id'              => 'required|exists:lead_positions,id',
            '*.name'   => 'required|string',
            '*.cost_per_unit'   => 'required|numeric|min:0',
            '*.vendor_code'     => 'nullable|string',
            '*.delivery_cost'   => 'nullable|numeric|min:0',
            '*.return_delivery' => 'nullable|numeric|min:0'
        ]);
        $lead = Lead::forBranch()->findOrFail($id);

        /** @var Customer $customer */
        $customer = $lead->customer;

        if (!$lead->contractorRequisite || !$customer->getRequisites()) {
            $error = ValidationException::withMessages([
                'errors' => ['Отсутсвуют реквизиты']
            ]);

            throw $error;
        }
        DB::beginTransaction();
        $sum = 0;
        $invoice = new DispatcherInvoice([
            'sum'               => 0,
            'alias'             => 1,
            'company_branch_id' => $this->companyBranch->id,
            'number'            => "{$lead->internal_number}-{$customer->internal_number}",
        ]);
        $invoice->customerRequisite()->associate($customer->legal_requisites
            ?: $customer->individual_requisites);
        $invoice->dispatcherLegalRequisite()->associate($lead->contractorRequisite);

        $lead->invoices()->save($invoice);
        foreach ($request->all() as $item) {

            $component = $lead->positions()->findOrFail($item['id']);
            /*  $ha sDelivery = $lead->invoices()->whereHas('orderComponents', function (Builder $q) use ($component) {
                  $q->where('order_workers.id', $component->id);
              })->exists();*/
            $itemsToSum = [
                'name'            => $item['name'],
                'order_duration'  => $component->order_duration,
                'order_type'      => $component->order_type,
                'vendor_code'     => $item['vendor_code'] ?? null,
                'date_from'       => $component->date_from,
                'date_to'         => $item['vendor_code'] ?? null,
                'cost_per_unit'   => numberToPenny($item['cost_per_unit']),
                'delivery_cost'   => numberToPenny($item['delivery_cost'] ?? 0),
                'return_delivery' => numberToPenny($item['return_delivery'] ?? 0),
            ];
            $pivot = new DispatcherInvoiceLeadPivot($itemsToSum);
            $invoice->leadPositions()->save($pivot);

            $sum += $itemsToSum['order_duration'] * $itemsToSum['cost_per_unit'] + $itemsToSum['delivery_cost'] + $itemsToSum['return_delivery'];
        }

        $invoice->update(['sum' => $sum]);
        if ($this->companyBranch->OneCConnection) {
            $data = [];
            $counter = 0;
            $deliveryCost = 0;

            foreach ($invoice->leadPositions as $component) {

                $data[] = [
                    'is_delivery'      => 0,
                    'description'      => $component->name,
                    'solo_description' => 1,
                    'vendor_code'      => $component->vendor_code,
                    'sum'              => $component->cost_per_unit / 100,
                    'amount'           => $component->order_duration,
                    'vat'              => $lead->contractorRequisite->vat_system === Price::TYPE_CASHLESS_VAT
                        ? 20
                        : 0,
                ];
                if ($component->delivery_cost || $component->return_delivery) {

                    if ($component->delivery_cost) {
                        ++$counter;
                        $deliveryCost += $component->delivery_cost;
                    }
                    if ($component->return_delivery) {
                        ++$counter;
                        $deliveryCost += $component->return_delivery;
                    }
                }
            }
            if ($counter > 0) {
                $data[] = [
                    'is_delivery' => $counter,
                    'vendor_code' => 'delivery',
                    'sum'         => $deliveryCost / 100,
                    'amount'      => 1,
                    'vat'         => $lead->contractorRequisite->vat_system === Price::TYPE_CASHLESS_VAT
                        ? 20
                        : 0,
                ];
            }
//            if ($this->companyBranch->OneCConnection) {
//                $connection = new OneCService($this->companyBranch);
//                $response =
//                    $connection->addInvoice($invoice->number, $invoice->id, $customer->getRequisites()->inn,
//                        $lead->contractorRequisite->inn, $data);
//
//                if ($response['code'] !== 200) {
//                    return response()->json([
//                        'errors' => $response['message']
//                    ], 400);
//                }
//
//                if (!empty($response['message']['Number'])) {
//                    $invoice->update([
//                        'number' => $response['message']['Number']
//                    ]);
//                }
//
//            }

        }
        \DB::commit();
    }


    function getDocuments($id)
    {
        $lead = Lead::query()
            ->forBranch()
            ->forDomain()
            ->findOrFail($id);

        return $lead->documents;
    }

    /**
     * Слздание новой заявки
     * @param CreateLeadRequest $request
     * @return Response
     * @throws \Exception
     */
    public function store(
        CreateLeadRequest $request,
        CalendarService   $service)
    {
        $data = $request->validated();

        $clientLead = toBool($request->input('client'));

        $leadService = new LeadService();

        if ($request->filled('customer_id') && !$request->filled('contract_id') && !$request->input('contract.subject_type')) {
            throw ValidationException::withMessages(['errors' => "Не выбран договор."]);
        }

        DB::beginTransaction();

        if ($request->filled('source')) {
            $leadService->setSource($request->input('source'));
        }

        if ($clientLead) {
            $leadService
                ->setCustomer($this->companyBranch)
                ->createNewLead($data, $this->companyBranch->id, $request->input('creator_id')
                    ?: Auth::id());

        } else {
            $reqData = explode('_', $request->input('contractor_requisite_id'));
            $req = $this->companyBranch->findRequisiteByType($reqData[1], $reqData[0]);
            if ($request->filled('customer_id')) {
                $customer = Customer::forCompany()->findOrFail($request->input('customer_id'));
                if ($request->input('contract_id')) {
                    $contract = Customer\CustomerContract::query()->findOrFail($request->input('contract_id'));
                } else {
                    $contract = $customer->generateNewContract($req, $request->input('contract', []));
                }
            }
            else {
                $customer = Customer::create([
                    'email'             => $request->input('email'),
                    'company_name'      => $request->input('customer.company_name'),
                    'contact_person'    => $request->input('contact_person'),
                    'region_id'         => $request->input('customer.region_id'),
                    'city_id'           => $request->input('customer.city_id'),
                    'phone'             => $request->input('phone'),
                    'creator_id'        => Auth::id(),
                    'company_branch_id' => $this->companyBranch->id,
                    'domain_id'         => RequestHelper::requestDomain()->id,
                ]);
                $requisite = $request->input('customer.requisite');
                $requisite['creator_id'] = Auth::id();
                $requisite['company_branch_id'] = $this->companyBranch->id;

                $reqData = explode('_', $request->input('contractor_requisite_id'));
                $req = $this->companyBranch->findRequisiteByType($reqData[1], $reqData[0]);
                $contract = $customer->generateContract($req);
                if ($request->filled('customer.requisite.inn') && $this->companyBranch->OneCConnection) {
                    $connection = new OneCService($this->companyBranch);
                    try {
                        $connection->checkClient($requisite, $contract->toArray());

                    } catch (\Exception $exception) {

                    }
                }
                // logger($request->input('has_requisite'));
                // DB::rollBack();
                // return response()->json([], 400);
                // die();
                if ($request->input('has_requisite') === 'legal') {

                    $customer->addLegalRequisites($requisite);
                }

                if (in_array($request->input('has_requisite'), [
                    User\IndividualRequisite::TYPE_PERSON,
                    User\IndividualRequisite::TYPE_ENTREPRENEUR,
                ])) {

                    $customer->addIndividualRequisites($requisite);
                }
            }
            $positions = [];

            foreach ($request->input('vehicles_categories') as $category) {
                if (isset($category['request_vehicles'])) {
                    foreach ($category['request_vehicles'] as $vehicle) {
                        $positions[] = [
                            'id'                 => $category['id'],
                            'order_type'         => $category['order_type'],
                            'order_duration'     => $category['order_duration'],
                            'count'              => $category['count'],
                            'machinery_model_id' => $category['machinery_model_id'],
                            'vehicle_id'         => intval($vehicle),
                            'date_from'          => $category['date_from'],
                            'start_time'         => $category['start_time'],
                        ];
                    }
                }
            }
            $dt = (empty($positions))
                ? $request->all()
                : array_merge($request->all(),
                    ['vehicles_categories' => $positions]);
            $leadService
                ->setDispatcherCustomer($customer)
                ->createNewLead($dt, $this->companyBranch->id, $request->input('creator_id')
                    ?: Auth::id());
        }

        $leadService->attachCall($request->input('call_id'));
        $leadService->attachMail($request->input('email_uuid'));

        $lead = $leadService->getLead();

        $lead->customerContract()->associate($contract);

        if (is_array($request->input('contacts'))) {
            foreach ($request->input('contacts') as $contact) {
                if (!empty($contact['id'])) {
                    $lead->contacts()->syncWithoutDetaching($contact['id']);
                }
            }
        }

        if ($contract){
            $documentService = new DocumentService([], $this->companyBranch);
            $documentService->getLeadContractUrl($contract, $lead);
        }

        DB::commit();

        $service->createEvent(
            type: GoogleCalendar::TYPE_APPLICATION,
            model: $lead,
            summary: $lead->title,
            dateFrom: Carbon::parse($lead->start_date),
            dateTo: Carbon::parse($lead->date_to),
            description: $lead?->comment,
            address: $lead?->address,
            manager: $lead->manager?->contact_person,
            customer: $customer?->company_name,
        );

        //  dispatch(new NewLeadNotifcations($lead));


        return response()->json($lead);
    }

    /**
     * Show the specified resource.
     * @param int $id
     * @return Lead
     */
    public function show($id)
    {
        /** @var Lead $lead */
        $lead = Lead::with('categories', 'positions', 'contacts', 'documentsPack')
            ->forBranch()
            ->forDomain()
            ->findOrFail($id);
        $response = $lead->toArray();
        $response['contractor_requisite_id'] = $lead->getContractorRequisiteTypeId();
        return $response;
    }


    /**
     * Update the specified resource in storage.
     * @param CreateLeadRequest $request
     * @param int $id
     * @return Response
     * @throws \Exception
     */
    public function update(
        CreateLeadRequest $request,
                          $id,
        CalendarService   $service
    )
    {
        $request->validated();

        $lead = Lead::forBranch()
            ->whereStatus(Lead::STATUS_OPEN)
            ->findOrFail($id);

        if (!$lead->can_edit) {
            return response()->json(['errors' => ['Невозможно редактировать в текущем статусе.']], 400);
        }
        DB::beginTransaction();
        $leadService = new LeadService();

        if ($request->filled('customer_id')) {
            $customer = Customer::forCompany()->findOrFail($request->input('customer_id'));
        } else {
            $customer = Customer::create([
                'email'             => $request->input('email'),
                'company_name'      => $request->input('company_name'),
                'contact_person'    => $request->input('contact_person'),
                'phone'             => $request->input('phone'),
                'creator_id'        => Auth::id(),
                'company_branch_id' => $this->companyBranch->id,
                'domain_id'         => RequestHelper::requestDomain()->id,
            ]);
            if ($request->input('customer_type') === 'individual') {

                $parts = explode(' ', $request->input('contact_person'));

                $parts = array_filter($parts, function ($value) {
                    return !!$value;
                });

                $customer->addIndividualRequisites([
                    'type'              => IndividualRequisite::TYPE_PERSON,
                    'company_branch_id' => $customer->company_branch_id,
                    'firstname'         => $parts[1] ?? null,
                    'middlename'        => $parts[2] ?? null,
                    'surname'           => $parts[0] ?? null,
                ]);

            }
        }
        if ($request->filled('source')) {
            $leadService->setSource($request->input('source'));
        }

        $leadService
            ->setDispatcherCustomer($customer)
            ->updateLead($lead, $request->all());


        if ($lead->integration) {
            $lead->integration->update([
                'status' => AmoLead::STATUS_PROCESSED
            ]);
        }
        $service->createEvent(
            type: GoogleCalendar::TYPE_APPLICATION,
            model: $lead,
            summary: $lead->title,
            dateFrom: Carbon::parse($lead->start_date),
            dateTo: Carbon::parse($lead->date_to),
            description: $lead?->comment,
            address: $lead?->address,
            manager: $lead->manager?->contact_person,
            customer: $customer?->company_name,
        );
        DB::commit();

        return response()->json($lead);
    }

    function changePublishType(
        Request $request,
                $id
    )
    {
        $request->validate([
            'publish_type' => 'required|in:my_proposals,all_contractors,for_companies',
        ]);
        $lead = Lead::forBranch()->whereStatus(Lead::STATUS_OPEN)->findOrFail($id);

        $lead->update($request->only('publish_type'));

        return response()->json();
    }

    /**
     * Remove the specified resource from storage.
     * @param int $id
     * @return Response
     */
    public function destroy($id)
    {
        $lead = Lead::forBranch()->forDomain()->findOrFail($id);
        $lead->delete();

        return response()->json();
    }

    function setSettings(
        Request $request,
                $id
    )
    {

        $request->validate([
            'type' => 'required|in:archive,reject',
        ]);

        switch ($request->input('type')) {
            case 'archive':
                if ($request->input('action') === 'push') {
                    $lead =
                        Lead::forBranch()->forDomain()->whereIn('status',
                            [Lead::STATUS_OPEN, Lead::STATUS_ACCEPT])->findOrFail($id);

                    $lead->close();
                }
                if ($request->input('action') === 'pull') {
                    $lead = Lead::forBranch()->archive()->findOrFail($id);

                    $lead->pullFromArchive();
                }
                break;
            case 'reject':

                $request->validate([
                    'reject'      => 'nullable|string|max:1000',
                    'reject_type' => 'required|string|in:' . LeadRejectReason::implodeInString(),
                ]);
                if ($request->input('order') === 1) {
                    $order = Order::query()->forBranch()->whereIn('status', [Lead::STATUS_ACCEPT])->findOrFail($id);
                    $service = new OrderService();
                    foreach ($order->workers as $worker) {
                        DB::transaction(fn() => $service->setOrder($order)->rejectApplication($worker->id,
                            $request->input('reject'))
                        );
                    }

                } else {
                    $lead = Lead::forBranch()->forDomain()->whereIn('status', [Lead::STATUS_OPEN])->findOrFail($id);

                    $lead->reject($request->input('reject_type'), $request->input('reject'));
                }

                break;
        }


        return response()->json();

    }

    function createHelper(Request $request)
    {
        $categories = Type::query()->with('tariffs')->orderBy('name');
        if ($request->filled('forBranch')) {
            $categories->whereHas('machines', function (Builder $q) {
                $q->forBranch();
            });
        }
        $cachePostfix = '';
        if ($request->filled('forServices')) {
            $cachePostfix = 'services';
            $categories->where(function ($q) {
                $q->whereHas('services', function (Builder $q) {
                    $q->forBranch();
                });
            });

        }
        if (!$request->filled('customers') || $request->input('customers') != 0) {

            $customers = Cache::rememberForever("{$this->companyBranch->id}_customers", function () {
                return Customer::with('contracts')->forBranch()->get()->toArray();
            });

        }
        $regions = Cache::remember('regions_' . app(RequestBranch::class)->company->domain->id, 1600, function () {
            return Region::forDomain()->with('cities')->orderBy('name')->get()->toArray();
        });
        $brands = Cache::remember('cached_brands', 9999, function () {
            return Brand::all()->toArray();
        });
        $branch = (int)$request->filled('forBranch');

        $categories = Cache::remember("{$this->companyBranch->id}_categories_{$branch}_{$cachePostfix}", 120,
            function () use
            (
                $categories
            ) {
                return Type::setLocaleNames($categories->get());
            });
        $rejectReasons = LeadRejectReason::all();

        $units = Cache::remember("units_directory", 9999, function () {
            return Unit::all();
        });

        // $employees = $this->companyBranch->employees()->with('contacts')->get();
        $documentsPack = $this->companyBranch->documentsPack->sortBy('id');
        $requisites = $this->companyBranch->getAllRequisites();
        //$requisites =  $this->companyBranch->getAllRequisites();


        return response()->json([
            'statuses'       => Lead::getStatuses(),
            'categories'     => $categories,
            'customers'      => $customers ?? [],
            // 'managers'       => $employees,
            'brands'         => $brands,
            'regions'        => $regions,
            'reject_reasons' => $rejectReasons,
            // 'employees'      => $employees,
            'documentsPack'  => $documentsPack,
            'requisites'     => $requisites,
            'units'          => $units,
        ]);
    }

    function info($id)
    {
        $lead = Lead::visible()->with([
            'city' => function ($q) {
                $q->with('region');
            },
            'customer.contacts',
            'audits.user',
            'customerContract'
        ])->findOrFail($id);

        if ($lead->company_branch_id === $this->companyBranch->id) {
            return $lead->fromDispatcher()
                ? DispatcherView::make($lead)
                : ClientView::make($lead);
        }
        return ContractorView::make($lead);
    }


    /**
     * Создание диспетчерского заказа на технику исполнителей ТРАНСБАЗЫ
     * @param SelectContrator $request
     * @param $id
     * @return \Illuminate\Http\JsonResponse
     * @throws \Exception
     */
    function selectContractor(
        SelectContrator $request,
                        $id
    )
    {

        $lead = Lead::query()
            ->forBranch()
            ->findOrFail($id);

        return LeadService::selectContractor($lead, $request);
    }

    function updateName(
        Request $request,
                $id
    )
    {
        $values = $request->all();
        //$request->validate([
        //    'title' => 'required|string|max:255'
        //]);
        $acceptableValues = [
            'tender',
            'first_date_rent',
            'accepted',
            'title',
            'comment',
            'tmp_status',
        ];
        $lead = Lead::query()
            ->forBranch()
            ->findOrFail($id);
        foreach ($values as $key => $value) {
            if (!in_array($key, $acceptableValues)) {
                continue;
            }

            $lead->fill([
                $key => $value
            ]);
            if($key === 'comment' && $value && (!$lead->tmp_status || $lead->tmp_status === Lead::STATUS_OPEN)) {
                $lead->tmp_status = Lead::STATUS_ACCEPT;
            }

            $lead->save();
        }


        return response()->json();
    }

    function updatePositionField(
        Request $request,
                $id,
                $positionId)
    {
        $lead = Lead::query()
            ->forBranch()
            ->findOrFail($id);

        $values = $request->all();
        $position = $lead->positions()->findOrFail($positionId);
        $position->update([
            'date_from' => "{$request->date_from} {$request->time_from}",
            'accepted'  => $request->boolean('accepted')
        ]);
        //$request->validate([
        //    'title' => 'required|string|max:255'
        //]);
//        $acceptableValues = [
//            'first_date_rent',
//            'accepted',
//        ];
//
//        $position = $lead->positions()->findOrFail($positionId);
//
//        foreach ($values as $key => $value) {
//            if(!in_array($key, $acceptableValues)){
//                continue;
//            }
//            $position->update([
//                $key => $value
//            ]);
//        }
    }

    /**
     * Создание диспетчерского заказа на собственную технику диспетчера или его подрядчиков
     * @param CreateMyOrderFromLead $request
     * @param $id
     * @return \Illuminate\Http\JsonResponse
     * @throws \Exception
     */
    function createOrder(
        CreateMyOrderFromLead $request,
                              $id
    )
    {

        $lead = Lead::query()
            ->forBranch()
            ->findOrFail($id);

        return LeadService::createDispatcherOrder($lead, $request);
    }


    function changeManager(
        Request $request,
                $id
    )
    {
        $lead = Lead::query()
            ->forBranch()
            ->active()
            ->findOrFail($id);
        $this->companyBranch->employees()->findOrFail($request->input('manager_id'));

        $lead->update([
            'creator_id' => $request->input('manager_id')
        ]);

        return response()->json();
    }

    /**
     * Создание предложения к заявке от исполнителя трансбазы.
     * Может добавить как свою технику, так и технику своих подрядчиков
     * @param CreateOffer $request
     * @param $lead_id
     * @return \Illuminate\Http\JsonResponse
     * @throws \Exception
     */
    function createOffer(
        CreateOffer $request,
                    $lead_id
    )
    {
        $lead = Lead::visible()->whereStatus(Lead::STATUS_OPEN)
            ->where('company_branch_id', '!=', $this->companyBranch->id)
            ->findOrFail($lead_id);


        DB::beginTransaction();

        $offer = new LeadOffer([
            'creator_id'        => Auth::id(),
            'company_branch_id' => $this->companyBranch->id,
        ]);
        $lead->offers()->save($offer);


        foreach ($request->input('offer') as $cart) {
            if ($cart['type'] === 'vehicle') {

                $vehicle = Machinery::query()
                    ->forBranch()
                    ->whereInCircle($lead->coords['lat'], $lead->coords['lng'])
                    ->findOrFail($cart['id']);

                $leadPosition = new LeadOfferPosition([
                    'category_id'       => $vehicle->type,
                    'lead_offer_id'     => $offer->id,
                    'company_branch_id' => $this->companyBranch->id,
                    'creator_id'        => Auth::id(),
                    'amount'            => numberToPenny($cart['amount']),
                    'value_added'       => numberToPenny($cart['value_added']
                        ?: 0),
                ]);

                $leadPosition->worker()->associate($vehicle);
                $leadPosition->save();

            } else {

                $vehicle = Vehicle::currentUser()->findOrFail($cart['id']);

                $leadPosition = new LeadOfferPosition([
                    'category_id'       => $vehicle->type_id,
                    'lead_offer_id'     => $offer->id,
                    'company_branch_id' => $this->companyBranch->id,
                    'creator_id'        => Auth::id(),
                    'amount'            => numberToPenny($cart['amount']),
                    'value_added'       => numberToPenny($cart['value_added']),
                ]);
                $leadPosition->worker()->associate($vehicle->contractor);
                $leadPosition->save();

            }
        }

        (new Subscription())->newOfferCustomerNotification($lead, $offer);

        DB::commit();
        return response()->json();

    }

    /**
     * Подтверждение предлоежния от исполнителя для диспетчерской заявки.
     * Диспетчер может указывать добавленные стоимости для любой позиции.
     * @param AcceptDispatcherOffer $request
     * @param $id
     * @return \Illuminate\Http\JsonResponse
     * @throws \Exception
     */
    function acceptDispatcherOffer(
        AcceptDispatcherOffer $request,
                              $id
    )
    {
        $lead = Lead::forBranch()->dispatcherLead()->findOrFail($id);

        return LeadService::acceptDispatcherOffer($lead, $request);
    }


    /**
     * Подтверждение предложения в заявке от исполнителя.
     * Создается оплачиваемы заказ. Данный метод только для клиентской заявки
     * @param AcceptOffer $request
     * @param $id
     * @return \Illuminate\Http\JsonResponse
     * @throws \Exception
     */
    function acceptOffer(
        AcceptOffer $request,
                    $id
    )
    {
        $lead = Lead::forBranch()->clientLead()->findOrFail($id);

        return LeadService::acceptOffer($lead, $request);

    }


    function getCommercialOffer(Request $request)
    {
        $request->validate([
            'models'       => 'nullable|array',
            //'tariffs' => 'required|array',
            // 'delivery_types' => 'required|array',
            'driver_types' => 'nullable',
            'lead_id'      => 'nullable',
            'template_id'  => 'required',
            // 'price_type' => 'nullable|in:'.implode(',', Price::getTypes()),
        ]);
        /*    $models = MachineryModel::query()->whereHas('machines', function ($q) {
                $q->forBranch();
            })->with(['machines' => function($q) {
                $q->forBranch();
            }])->get();*/

        //$vehicles = Machinery::query()->forBranch()->whereIn('id', $v->pluck('id')->toArray())->get();

        $offer = new CommercialOfferService($this->companyBranch, $request->input('template_id'));

        $offer->setContractorRequisites($this->companyBranch->entity_requisites->first())
            ->setModelsList($request->input('models'))
            ->setManager(Auth::user())
            ->setAttributes([], $request->input('delivery_types'), [
                ($request->input('driver_types')
                    ?: 'without_driver')
            ], Price::TYPE_CASHLESS_WITHOUT_VAT, $request->input('text'), $request->input('expires_at', ''));
        /** @var Lead $lead */
        $lead = Lead::query()->forBranch()->find($request->input('lead_id'));
        if ($lead) {
            $offer->setLead($lead);
            $lead->update([
                'status'  => Lead::STATUS_KP,
                'tmp_status'  => Lead::STATUS_KP,
                'kp_date' => now()
            ]);
        }
        return $offer->getOffer();

    }

    function getAvailableMachineries(
        Request $request,
                $leadId,
                $positionId
    )
    {
        /** @var Lead $lead */
        $lead = Lead::query()
            ->forBranch()
            ->findOrFail($leadId);

        //$position = $lead->positions()->findOrFail($positionId);

        $machineries = LeadService::getMachineriesForPosition($lead, $positionId);

        return $machineries;
    }

    function mergePdf(Request $request, $id)
    {
        $lead = Lead::find($id);
        $dt = Carbon::now()->format('d.m.Y H-i');
        $pdfName = "Документы по заявке аренды № $lead->internal_number от $dt (PDF)";
        $pdfPath = config('app.upload_tmp_dir') . "/$pdfName.pdf";

        $merger = new PDFMerger();
        $documents = OrderDocument::query()->whereIn('id',$request->documents_ids)->get();
        if($documents->contains(fn($doc) => pathinfo($doc->url, PATHINFO_EXTENSION) === 'docx')) {
            return  $this->mergeDocx($lead, $documents->toArray());
        }
        $paths = [];
        foreach ($documents as $document) {
            $path = config('app.upload_tmp_dir').'/'.time().$document->id.'.pdf';
            $paths[] = $path;
            Storage::disk('public_disk')
                ->put(
                    $path,
                    Storage::disk()->get($document->url)
                );

            $merger->addPdf(public_path($path));
        }

        $merger->merge('file', public_path($pdfPath));

        Storage::disk()->put($pdfPath, Storage::disk('public_disk')->get($pdfPath));

        $document = $lead->addDocument($pdfName, $pdfPath);

        Storage::disk('public_disk')->delete($paths);
        return $document;
    }
}
