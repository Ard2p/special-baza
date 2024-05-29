<?php

namespace Modules\Orders\Http\Controllers;

use AnourValar\EloquentSerialize\Facades\EloquentSerializeFacade;
use App\Events\ServiceCenterCreatedEvent;
use App\Helpers\RequestHelper;
use App\Machines\FreeDay;
use App\Service\Google\CalendarService;
use App\Service\RequestBranch;
use App\User\IndividualRequisite;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Jurosh\PDFMerge\PDFMerger;
use Modules\AdminOffice\Entities\Filter;
use Modules\CompanyOffice\Entities\Company\CompanyBranch;
use Modules\CompanyOffice\Entities\Company\GoogleCalendar;
use Modules\CompanyOffice\Services\CompanyRoles;
use Modules\ContractorOffice\Entities\Services\CustomService;
use Modules\ContractorOffice\Entities\Vehicle\TechnicalWork;
use Modules\Dispatcher\Entities\Customer;
use Modules\Dispatcher\Entities\DispatcherInvoice;
use Modules\Dispatcher\Entities\Lead;
use Modules\Integrations\Services\OneC\OneCService;
use Modules\Orders\Entities\Order;
use Modules\Orders\Entities\OrderComponent;
use Modules\Orders\Entities\OrderDocument;
use Modules\Orders\Entities\Service\ServiceCenter;
use Modules\Orders\Entities\Service\ServiceCenterWorksPivot;
use Modules\Orders\Entities\Service\ServiceWork;
use Modules\Orders\Http\Requests\ServiceCenter\ServiceRequest;
use Modules\Orders\Services\OrderDocumentService;
use Modules\Orders\Transformers\ServiceCenterResource;
use Modules\PartsWarehouse\Entities\Shop\Parts\PartsSale;
use Modules\PartsWarehouse\Entities\Stock\Item;
use Modules\PartsWarehouse\Entities\Warehouse\Part;

class ServiceCenterController extends Controller
{
    /** @var CompanyBranch */
    private $companyBranch;

    public function __construct(
        Request       $request,
        RequestBranch $companyBranch)
    {
        $this->companyBranch = $companyBranch->companyBranch;

        $block = $this->companyBranch->getBlockName(CompanyRoles::BRANCH_PAYMENTS);

        $this->middleware("accessCheck:{$block}," . CompanyRoles::ACTION_SHOW)->only([
            'index', 'show'
        ]);
        $this->middleware("accessCheck:{$block}," . CompanyRoles::ACTION_CREATE)->only(['store', 'updatePart']);

    }


    /**
     * Display a listing of the resource.
     * @return Response
     */
    public function index(Request $request)
    {
        $query = ServiceCenter::query()->forBranch()
            ->withPaidInvoiceSum()
            ->withInvoiceSum()
            ->with(['manager', 'base', 'technicalWork', 'parts', 'works', 'customer',
            'contractorRequisite',
            'bankRequisite',
            'documentsPack',
            ]);

        $filter = new Filter($query);
        $filter->getEqual([
            'customer_id' => 'customer_id',
            'base_id'     => 'base_id',
            'type'        => 'type',
            'creator_id'  => 'creator_id',
        ])->getLike([
            'internal_number' => 'internal_number',
            'created_at'      => 'created_at',
        ])->getEqual([
           'is_warranty' => 'is_warranty',
            'is_plan' => 'is_plan',
        ], true);

        if($request->filled('comment')) {
            $query->where(function (Builder $builder) use ($request) {
               $builder->where('description', 'like', "%$request->comment%");
               $builder->orWhere('note', 'like', "%$request->comment%");
               $builder->orWhere('comment', 'like', "%$request->comment%");
            });
        }
        if($request->anyFilled(['date_from', 'date_to'])) {
            $query->forPeriod(
                $request->date_from ? Carbon::parse($request->date_from)->startOfDay() : now()->addMonth()->startOfDay(),
                $request->date_to ? Carbon::parse($request->date_to)->endOfDay() : now()->addMonth()->endOfDay(),
            );
        }
        if($request->input('contract')) {
            $query->whereRelation('contract', 'number', 'like', "%{$request->input('contract')}%");
        }
        if($request->input('phone')) {
            $query->whereRelation('customer', 'phone', 'like', "%{$request->input('phone')}%");
        }

//        $filterPhone = trimPhone($request->input('phone'));
//        if ($filterPhone !== '') {
//            $query->where(function (Builder $builder) use ($filterPhone) {
//                return $builder->filterContactPerson(null, (int)$filterPhone);
//            });
//        }

        if($request->filled('status_tmp') && is_array($request->input('status_tmp'))) {
            $query->whereIn('status_tmp', $request->input('status_tmp', []));
        }
        if ($request->filled('mechanic_id')) {
            $query->whereHas('workers', fn (Builder $workersBuilder) => $workersBuilder->where($workersBuilder->qualifyColumn('id'), $request->input('mechanic_id')));
        }

        if ($request->filled('name')) {
            $query->where(function (Builder $q) use ($request) {
                $q->whereHas(
                    'machinery', fn (Builder $machineryBuilder) => $machineryBuilder->where($machineryBuilder->qualifyColumn('name'), 'like', "%{$request->input('name')}%")
                )
                    ->orWhere('name','like', "%{$request->input('name')}%")
                    ->orWhere('note','like', "%{$request->input('name')}%");
            });
        }
        if ($request->filled('category_id')) {
            $query->whereHas('machinery', function ($q) use
            (
                $request
            ) {
                $q->where('type', $request->input('category_id'));
            });
        }
        if ($request->filled('pay_status')) {

            switch ($request->input('pay_status')) {
                case 'paid':

                    // $query->where('`invoice_sum` >= `paid_sum`');
                    $query->whereHas('invoices', function (Builder $q) {
                        $q->havingRaw('SUM(paid_sum) >= SUM(sum) ');
                        //$q->whereRaw('SUM(`dispatcher_invoices`.`paid_sum`) >= `amount`');
                    });
                    break;
                case 'partial':
                case 'not_paid':
                    $query->where(function ($query) {
                        $query->whereHas('invoices', function (Builder $q) {
                            $q->havingRaw('SUM(paid_sum) < SUM(sum) ');
                            //$q->whereRaw('SUM(`dispatcher_invoices`.`paid_sum`) >= `amount`');
                        })->orWhereDoesntHave('invoices');
                    });

                    break;
                /*  case 'not_paid':
                      $orders->has('pays', '=', 0);
                      break;*/
            }
        }
        //$ids = (clone $query)->pluck('id')->toArray();

        $paidSum = DispatcherInvoice::query()->where('owner_type', ServiceCenter::class)
            ->whereIn('owner_id', (clone $query)->select('id'));

        $invoiceSum = DispatcherInvoice::query()->where('owner_type', ServiceCenter::class)
            ->whereIn('owner_id',  (clone $query)->select('id'));

        $collection = ServiceCenterResource::collection($query->orderBy('id', 'desc')->paginate($request->per_page));

        $collection->additional([
            'paid_sum'    => $paidSum->sum('paid_sum'),
            'invoice_sum' => $invoiceSum->sum('sum')
        ]);
        return $collection;
    }


    /**
     * Store a newly created resource in storage.
     * @param Request $request
     * @return Response
     */
    public function store(ServiceRequest $request, CalendarService $googleService)
    {
        if ($request->input('type') === 'out') {

            if($request->filled('customer_id') && !$request->filled('contract_id') && !$request->input('contract.subject_type')) {
                throw ValidationException::withMessages(['errors' => "Не выбран договор."]);
            }

            if ($request->filled('customer_id')) {
                $customer = Customer::forCompany()->findOrFail($request->input('customer_id'));

            } else {
                $customer = Customer::create([
                    'email'             => $request->input('email'),
                    'company_name'      => $request->input('customer.company_name'),
                    'contact_person'    => $request->input('contact_person'),
                    'region_id'         => $request->input('customer.region_id'),
                    'city_id'           => $request->input('customer.city_id'),
                    'phone'             => $request->input('phone'),
                    'creator_id'        => Auth::id(),
                    'company_branch_id' => $this->companyBranch->id,
                    'domain_id'         => RequestHelper::requestDomain('id'),
                ]);
                $requisite = $request->input('customer.requisite');
                $requisite['creator_id'] = Auth::id();
                $requisite['company_branch_id'] = $this->companyBranch->id;

                // logger($request->input('has_requisite'));
                // DB::rollBack();
                // return response()->json([], 400);
                // die();
                if ($request->input('has_requisite') === 'legal') {

                    $customer->addLegalRequisites($requisite);
                }


                if (in_array($request->input('has_requisite'), [
                    IndividualRequisite::TYPE_PERSON,
                    IndividualRequisite::TYPE_ENTREPRENEUR,
                ])) {

                    $customer->addIndividualRequisites($requisite);
                }
            }
        }
        \DB::beginTransaction();
        $dateFrom =
            $request->input('date_from');
        $dateTo =
            $request->input('date_to');

        $center = new ServiceCenter([
            'is_plan'              => !($request->input('type') === 'out') && $request->boolean('is_plan') && !$request->boolean('is_warranty'),
            'is_warranty'              => !($request->input('type') === 'out') && $request->boolean('is_warranty') && ! $request->boolean('is_plan'),
            'name'              => $request->input('name'),
            'client_vehicles'              => $request->input('client_vehicles'),
            'type'              => $request->input('type'),
            'phone'             => $request->input('phone'),
            'contact_person'    => $request->input('contact_person'),
            'bank_requisite_id'    => $request->input('bank_requisite_id'),
            'description'       => $request->input('description'),
            'note'              => $request->input('note'),
            'status_tmp'        => 'new',
            'address'           => $request->input('address'),
            'address_type'      => $request->input('address_type'),
            'date_from'         => $dateFrom,
            'date_to'           => $dateTo,
            'creator_id'        => $request->input('creator_id'),
            'documents_pack_id' => $request->input('documents_pack_id'),
            'base_id'           => $request->input('base_id'),
            'machinery_id'      => $request->input('machinery_id'),
            'comment'      => $request->input('comment'),
            'company_branch_id' => $this->companyBranch->id
        ]);
        $center->save();
        if ($request->input('contractor_requisite_id')) {

            $reqData = explode('_', $request->input('contractor_requisite_id'));
            if ($req = $this->companyBranch->findRequisiteByType($reqData[1], $reqData[0])) {
                $center->contractorRequisite()->associate($req);
            }

            if ($request->input('type') === 'out') {
                if($request->input('contract_id')) {
                    $contract = Customer\CustomerContract::query()->findOrFail($request->input('contract_id'));
                }else {
                    $contract = $customer->generateNewContract($req, $request->input('contract', []), 'service');
                }
                $center->contract()->associate($contract);
            }
        }


        if ($request->input('order_id')) {
            $component = OrderComponent::query()->whereHas('order', function ($q) {
                $q->forBranch();
            })->findOrFail($request->input('order_id'));

            $center->order()->associate($component);
            $center->machinery_id = $component->worker->id;

            $center->save();

        }

        if($request->input('machinery_id')) {
            /** @var TechnicalWork currentService */
            $service = TechnicalWork::create([
                'engine_hours' => $request->input('work_hours'),
                'type' => 'maintenance',
                'description' => '',
                'machinery_id' => $request->input('machinery_id'),
                'service_center_id' => $center->id,
            ]);
            FreeDay::create([
                'startDate' => $dateFrom,
                'endDate' => $dateTo,
                'type' => 'busy',
                'machine_id' => $request->input('machinery_id'),
                'creator_id' => $request->input('creator_id'),
                'technical_work_id' => $service->id
            ]);
            $service->mechanics()->sync($request->input('workers', []));
        }

        $center->customer()->associate($request->input('type') === 'out' ? $customer : $this->companyBranch);
        $center->save();
        $center->workers()->sync($request->input('workers', []));

        ServiceCenterCreatedEvent::dispatch($center);
        \DB::commit();

        return response()->json($center);
    }


    function clipOrderComponent(
        Request $request,
                $id)
    {
        $center = ServiceCenter::query()->forBranch()->findOrFail($id);

        $component = OrderComponent::query()->whereHas('order', function ($q) {
            $q->forBranch();
        })->findOrFail($request->input('id'));

        $center->order()->associate($component);
        $center->machinery_id = $component->worker->id;

        $center->save();

        return response()->json();
    }

    function unClipOrderComponent(
        Request $request,
                $id)
    {
        $center = ServiceCenter::query()->forBranch()->findOrFail($id);

        $center->order_type = null;
        $center->order_id = null;

        $center->save();

        return response()->json();
    }

    function getWorks(
        Request $request,
                $id)
    {
        $center = ServiceCenter::query()->with('worksPivot', 'works.unit')->forBranch()->findOrFail($id);

        return collect($center->works->toArray())->map(function ($work) use ($center){
            $work['pivot'] = $center->worksPivot->where('custom_service_id', $work['id'])->first();
            return $work;
        });
    }

    function addWorks(
        Request $request,
                $serviceId)
    {
        $request->validate([
            'id'    => ($request->input('type') === 'exists'
                ? 'required'
                : 'nullable'),
            'price' => 'required|numeric|min:0',
            'count' => 'required|numeric|min:1',
            'type'  => 'required|in:new,exists',
            'comment'  => 'nullable|string:max:255',
        ]);

        $service = ServiceCenter::query()->forBranch()->findOrFail($serviceId);

        \DB::beginTransaction();


            $work = CustomService::query()->forBranch()->findOrFail($request->input('id'));

        $service->works()->syncWithoutDetaching([
            $work->id => [
                'count' => $request->input('count'),
                'price' => $request->input('price'),
            ]
        ]);
        ServiceCenterWorksPivot::query()->firstWhere(['service_center_id' => $service->id, 'custom_service_id' => $work->id])->update([
                'comment' => $request->input('comment')
            ]);
        $service->update([
            'status_tmp' => ServiceCenter::STATUS_IN_PROGRESS
        ]);
        \DB::commit();
        return response()->json($work);
    }

    function removeWorks(
        Request $request,
                $serviceId)
    {
        $service = ServiceCenter::query()->forBranch()->findOrFail($serviceId);

        $service->works()->detach([$request->input('id')]);

        return response()->json();
    }

    function attachPart(
        Request $request,
                $serviceId)
    {
        $request->validate([
            'amount'        => 'required|numeric|min:1',
            'cost_per_unit' => 'required|numeric|min:1',
        ]);

        /** @var ServiceCenter $service */
        $service = ServiceCenter::query()->forBranch()->findOrFail($serviceId);

        /** @var Item $part */
        $part = Item::query()->forBranch()->findOrFail($request->input('id'));

        $serialAccounting = toBool($request->input('serial', false));

        \DB::beginTransaction();

        $service->parts()->save(
            new Item([
                'part_id'           => $part->part_id,
                'stock_id'          => $part->stock_id,
                'unit_id'           => $part->unit_id,
                'cost_per_unit'     => numberToPenny($request->input('cost_per_unit')),
                'comment'           => $request->input('comment'),
                'amount'            => $serialAccounting
                    ? 0
                    : $request->input('amount'),
                'serial_accounting' => $serialAccounting,
                'company_branch_id' => $this->companyBranch->id,
            ])
        );

        $service->update([
            'date' => now(),
            'status_tmp' => ServiceCenter::STATUS_IN_PROGRESS
        ]);

        \DB::commit();
    }

    function detachPart(
        Request $request,
                $serviceId)
    {
        $service = ServiceCenter::query()->forBranch()->findOrFail($serviceId);

        $service->parts()->findOrFail($request->input('id'))->delete();

        //    $service->([$part->id]);
    }

    function getParts(
        Request $request,
                $serviceId)
    {
        $service = ServiceCenter::query()->forBranch()->findOrFail($serviceId);

        return $service->parts;

        //    $service->([$part->id]);
    }

    /**
     * Show the specified resource.
     * @param int $id
     * @return Response
     */
    public function show($id)
    {
        return ServiceCenterResource::make(
            ServiceCenter::query()
                ->with('customer')
                ->forBranch()
                ->findOrFail($id)
        );
    }


    /**
     * Update the specified resource in storage.
     * @param Request $request
     * @param int $id
     * @return Response
     */
    public function update(
        ServiceRequest $request,
        $id, CalendarService $googleService)
    {
        \DB::beginTransaction();
        /** @var ServiceCenter $serviceCenter */
        $serviceCenter = ServiceCenter::query()->forBranch()->findOrFail($id);

        if ($serviceCenter->customer instanceof CompanyBranch) {
            $customer = $this->companyBranch;
        } else {
            $customer = Customer::forCompany()->find($request->input('customer_id'));
        }

        $dateFrom =
            $request->input('date_from');
        $dateTo =
            $request->input('date_to');
        $oldMachineryId = $serviceCenter->machinery_id;

        $serviceCenter->update([
            'is_plan'              => !($request->input('type') === 'out') && $request->boolean('is_plan'),
            'name'              => $request->input('name'),
            'bank_requisite_id'              => $request->input('bank_requisite_id'),
            'type'              => $request->input('type'),
            'client_vehicles'              => $request->input('client_vehicles'),
            'phone'             => $request->input('phone'),
            'contact_person'    => $request->input('contact_person'),
            'description'       => $request->input('description'),
            'note'              => $request->input('note'),
            'machinery_id'      => $request->input('machinery_id'),
            'date_from'         => $dateFrom,
            'date_to'           => $dateTo,
            'status_tmp'        => $request->input('status_tmp'),
            'address'           => $request->input('address'),
            'address_type'      => $request->input('address_type'),
            'creator_id'        => $request->input('creator_id'),
            'base_id'           => $request->input('base_id'),
            'documents_pack_id' => $request->input('documents_pack_id'),
            'comment' => $request->input('comment'),
            'company_branch_id' => $this->companyBranch->id
        ]);
        if($request->input('contract_id') && $serviceCenter->customer instanceof Customer) {
            $contract = $serviceCenter->customer->serviceContracts()->findOrFail($request->input('contract_id'));
            $serviceCenter->contract()->associate($contract);
        }

        if(in_array($request->input('status_tmp'), [ServiceCenter::STATUS_DONE, ServiceCenter::STATUS_ISSUED])) {
            $serviceCenter->machinery?->update([
                'engine_hours_after_tw' => 0,
                'days_after_tw' => 0,
            ]);
        }

        if($request->input('status_tmp') === ServiceCenter::STATUS_DONE) {
            if ($this->companyBranch->OneCConnection) {
                $connection = new OneCService($this->companyBranch);
                $stock = $serviceCenter->parts->first()->stock;
                $response = $connection->partDocument([
                    'inn' => $serviceCenter->contractorRequisite->inn,
                    'stock_key' => $stock->onec_uuid,
                    'items' => $serviceCenter->parts->map(function ($part) {
                        return [
                            'amount' => $part->amount,
                            'comment'           => ' ',
                            'vendor_code'           => $part->part->vendor_code
                        ];
                    })
                ]);
                if ($response['code'] !== 200) {

                    return response()->json([
                        'errors' => $response['message']
                    ], 400);
                }
            }
        }

        $serviceCenter->customer()->associate($customer);

        if ($request->input('contractor_requisite_id')) {
            $reqData = explode('_', $request->input('contractor_requisite_id'));
            if ($req = $this->companyBranch->findRequisiteByType($reqData[1], $reqData[0])) {
                $serviceCenter->contractorRequisite()->associate($req);
            }
        }

        $serviceCenter->save();

        $serviceCenter->workers()->sync($request->input('workers', []));
        $techWork =  $serviceCenter->technicalWork;
        if($oldMachineryId && $oldMachineryId !== (int) $request->input('machinery_id')) {
            if($techWork) {
                $serviceCenter->machinery->freeDays()->where('technical_work_id', $techWork->id)->delete();
            }
        }
        if($request->input('machinery_id')) {
            $fileds = [
                'type' => 'maintenance',
                'description' => '',
                'engine_hours' => $request->input('work_hours'),
                'machinery_id' => $request->input('machinery_id'),
                'service_center_id' => $serviceCenter->id,
            ];
            $techWork =  $serviceCenter->technicalWork;
            if($techWork) {
                $techWork->update([
                    'engine_hours' => $request->input('work_hours'),
                    'machinery_id' => $request->input('machinery_id'),
                ]);
            }else {
                /** @var TechnicalWork currentService */
                $techWork = TechnicalWork::create($fileds);
            }
            $techWork->periods()->delete();

            FreeDay::create([
                'startDate' => $dateFrom,
                'endDate' => $dateTo,
                'type' => 'busy',
                'machine_id' => $request->input('machinery_id'),
                'creator_id' => $request->input('creator_id'),
                'technical_work_id' => $techWork->id
            ]);
            $techWork->mechanics()->sync($request->input('workers', []));
        }

        ServiceCenterCreatedEvent::dispatch($serviceCenter);
        \DB::commit();

        return $serviceCenter;
    }

    /**
     * Remove the specified resource from storage.
     * @param int $id
     * @return Response
     */
    public function destroy($id)
    {
        //
    }

    function getDocuments($id)
    {
        $sale = ServiceCenter::query()->forBranch()->findOrFail($id);

        return $sale->documents;
    }

    function getApplication(
        Request $request,
                $id)
    {
        $serviceCenter = ServiceCenter::query()->forBranch()->findOrFail($id);

        $document = new OrderDocumentService($request->all());


        if($request->boolean('preview')) {
            return response()->json([
                'preview' =>  $document->generateServiceApplication($serviceCenter)
            ]);
        }
        $serviceCenter->update([
            'status_tmp' => ServiceCenter::STATUS_ACCEPT
        ]);
        return response()->json([
            'url' => $document->generateServiceApplication($serviceCenter)
        ]);
    }

    function getContract(
        Request $request,
                $id)
    {
        $serviceCenter = ServiceCenter::query()->forBranch()->findOrFail($id);

        $document = new OrderDocumentService($request->all());

        if($request->boolean('preview')) {
            return response()->json([
                'preview' =>  $document->generateServiceContract($serviceCenter)
            ]);
        }

        return response()->json([
            'url' => $document->generateServiceContract($serviceCenter)
        ]);
    }

    function getReturnAct(
        Request $request,
                $id)
    {
        $serviceCenter = ServiceCenter::query()->forBranch()->findOrFail($id);

        $document = new OrderDocumentService($request->all());

        if($request->boolean('preview')) {
            return response()->json([
                'preview' =>  $document->generateServiceReturnAct($serviceCenter)
            ]);
        }

        return response()->json([
            'url' => $document->generateServiceReturnAct($serviceCenter)
        ]);
    }

    function getServicesAct(Request $request, $id)
    {
        $serviceCenter = ServiceCenter::query()->forBranch()->findOrFail($id);

        $document = new OrderDocumentService($request->all());

        if($request->boolean('preview')) {
            return response()->json([
                'preview' =>  $document->generateServiceServicesAct($serviceCenter)
            ]);
        }


        return response()->json([
            'url' => $document->generateServiceServicesAct($serviceCenter)
        ]);
    }

    function updatePart(Request $request, $id)
    {
        $request->validate([
            'item' => 'required|array',
            'type' => 'required|in:part,service'
        ]);
        $serviceCenter = ServiceCenter::query()->forBranch()->findOrFail($id);

        if($request->input('type') === 'service') {
            $serviceCenter->works()->syncWithoutDetaching([
                $request->input('item.id') => [
                    'count' => $request->input('item.count'),
                    'price' => $request->input('item.price'),
                ]
            ]);
            ServiceCenterWorksPivot::query()
                ->firstWhere(['custom_service_id' => $request->input('item.id'), 'service_center_id' => $serviceCenter->id])
                ->update(['comment' => $request->input('item.comment')]);
        }else {

            $serviceCenter->parts()->where([
                'part_id' => $request->input('item.part_id'),
                'stock_id' => $request->input('item.stock_id'),
                'unit_id' => $request->input('item.unit_id'),
            ])->get()->each->update([
                    'cost_per_unit'     => numberToPenny($request->input('item.cost_per_unit')),
                    'amount'     => $request->input('item.amount'),
                    'comment'     => $request->input('item.comment'),
            ]);
        }
    }

    function mergePdf(Request $request, ServiceCenter $serviceCenter)
    {
        $dt = Carbon::now()->format('d.m.Y H-i');
        $pdfName = "Документы по сервису № $serviceCenter->internal_number от $dt (PDF)";
        $pdfPath = config('app.upload_tmp_dir') . "/$pdfName.pdf";

        $merger = new PDFMerger();
        $documents = OrderDocument::query()->whereIn('id',$request->documents_ids)->get();
        if($documents->contains(fn($doc) => pathinfo($doc->url, PATHINFO_EXTENSION) === 'docx')) {
            return  $this->mergeDocx($serviceCenter, $documents->toArray());
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

        $document = $serviceCenter->addDocument($pdfName, $pdfPath);

        Storage::disk('public_disk')->delete($paths);
        return $document;
    }

    function mergeDocx(ServiceCenter $serviceCenter, $documents)
    {
        $dt = Carbon::now()->format('d.m.Y H-i');
        $docxName = "Документы по сервису № $serviceCenter->internal_number от $dt";
        $docxPath = config('app.upload_tmp_dir') . "/$docxName.docx";

        $lastDoc = end($documents);

        $mainDoc = new \PhpOffice\PhpWord\PhpWord();
        $paths = [];
        foreach($documents as $document) {
            $path = config('app.upload_tmp_dir').'/'.time().$document['id'].'.docx';
            $paths[] = $path;
            Storage::disk('public_disk')
                ->put(
                    $path,
                    Storage::disk()->get($document['url'])
                );

            $appendDoc = \PhpOffice\PhpWord\IOFactory::load($path);
            foreach ($appendDoc->getSections() as $section) {
                $mainDoc->addSection($section);
            }

            // Add page break after every appended doc except the last one
            if($document['id'] != $lastDoc['id']) {
                $section = $mainDoc->addSection();
                $section->addPageBreak();
            }
        }
        $mainDoc->save($docxPath);

        Storage::disk()->put($docxPath, Storage::disk('public_disk')->get($docxPath));

        $document = $serviceCenter->addDocument($docxName, $docxPath);

        Storage::disk('public_disk')->delete($docxPath);
        Storage::disk('public_disk')->delete($paths);

        return $document;
    }

    function changeContract(Request $request, $id)
    {
        $request->validate(['contract_id' => 'required']);

        $serviceCenter = ServiceCenter::forBranch()->findOrFail($id);
        $contract = $serviceCenter->customer->serviceContracts()->findOrFail($request->input('contract_id'));

        DB::beginTransaction();

        $serviceCenter->contract()->associate($contract);
        $serviceCenter->save();

        DB::commit();

        return response()->json();
    }
}
