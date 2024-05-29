<?php

namespace Modules\Dispatcher\Http\Controllers;

use AnourValar\EloquentSerialize\Facades\EloquentSerializeFacade;
use App\Helpers\RequestHelper;
use App\Machines\Brand;
use App\Machines\Type;
use App\Service\RequestBranch;
use App\Support\Region;
use App\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Laravel\Octane\Facades\Octane;
use Modules\AdminOffice\Entities\Filter;
use Modules\CompanyOffice\Entities\Company\ContactEmail;
use Modules\CompanyOffice\Entities\CompanyTag;
use Modules\CompanyOffice\Services\CompanyRoles;
use Modules\CompanyOffice\Services\ContactsService;
use Modules\Dispatcher\Entities\Customer;
use Modules\Dispatcher\Entities\DispatcherInvoice;
use Modules\Dispatcher\Entities\DispatcherInvoiceDepositTransfer;
use Modules\Dispatcher\Entities\Lead;
use Modules\Dispatcher\Entities\ManagerNote;
use Modules\Dispatcher\Http\Requests\CreateCustomerRequest;
use Modules\Dispatcher\Services\CustomerService;
use Modules\Dispatcher\Transformers\CustomerContractResource;
use Modules\Dispatcher\Transformers\CustomerEdit;
use Modules\Dispatcher\Transformers\CustomerInfo;
use Modules\Dispatcher\Transformers\CustomersList;
use Modules\Integrations\Services\OneC\OneCService;
use Modules\Integrations\Transformers\Telephony\TelpehonyHistoryResource;
use Modules\Orders\Entities\Order;
use Modules\Orders\Entities\Payments\InvoicePay;
use Modules\Orders\Entities\Service\ServiceCenter;
use Rap2hpoutre\FastExcel\FastExcel;

class CustomersController extends Controller
{

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

        $block = $this->companyBranch->getBlockName(CompanyRoles::BRANCH_CLIENTS);
        $this->middleware("accessCheck:{$block}," . CompanyRoles::ACTION_SHOW)->only('index', 'getInfo', 'callsHistory',
            'show', 'searchByEmails', 'createHelper');

        $this->middleware("accessCheck:{$block}," . CompanyRoles::ACTION_CREATE)->only([
            'store', 'update', 'import', 'setTags',
            'getComments', 'postComment', 'deleteComment', 'blackList', 'setSettings', 'removeDuplicate'
        ]);
        $this->middleware("accessCheck:{$block}," . CompanyRoles::ACTION_DELETE)->only(['destroy']);


        if ($request->filled('contacts')) {
            $contacts = $request->input('contacts');
            foreach ($contacts as &$contact) {
                $contact['phone'] = trimPhone($contact['phone'] ?? '');
            }
            $request->merge([
                'contacts' => $contacts
            ]);
        }

    }

    function searchByEmails(Request $request)
    {
        $request->validate([
            'search_word' => 'required|string|min:2'
        ]);
        $sw = $request->input('search_word');

        $emails = ContactEmail::with('contact')->whereHas('contact', function (Builder $q) {
            $q->forBranch();
        })->where(function (Builder $q) use
        (
            $sw
        ) {
            $q->where('email', 'like', "%$sw%");
            $q->orWhereHas('contact', function (Builder $q) use
            (
                $sw
            ) {

                $q->whereHasMorph('owner', [Customer::class], function (Builder $q) use
                (
                    $sw
                ) {
                    $q->where('company_name', 'like', "%{$sw}%");
                });
            });
        })->get();

        return $emails->map(function ($item) use
        (
            $sw
        ) {

            return [
                'email'          => $item->email,
                'contact_person' => $item->contact->contact_person,
                'label'          => "{$item->email} ({$item->contact->contact_person}) {$sw}",
            ];
        });
    }

    /**
     * Display a listing of the resource.
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function index(Request $request)
    {
        $customers = Customer::query()
            ->forDomain()
            ->forBranch()
            ->select([
                DB::raw("dispatcher_customers.id"),
                DB::raw("dispatcher_customers.creator_id"),
                DB::raw("dispatcher_customers.internal_number"),
                DB::raw("dispatcher_customers.company_name"),
                DB::raw("dispatcher_customers.address"),
                DB::raw("dispatcher_customers.contact_person"),
                DB::raw("dispatcher_customers.phone"),
                DB::raw("dispatcher_customers.email"),
                DB::raw("dispatcher_customers.channel as customer_channel"),
                DB::raw("dispatcher_customers.source as customer_source"),
                DB::raw("dispatcher_customers.created_at"),
                DB::raw("dispatcher_customers.in_black_list"),
                DB::raw("dcc.current_number as contract_number"),
                DB::raw("o.amount / 100                  as orders_sum"),
                DB::raw("invoices.paid_sum / 100         as invoice_pays_sum"),
                DB::raw("o.ctn                           as orders_count"),
                DB::raw("count(dl.id)                    as leads_count"),
                DB::raw("cities.name                     as city"),
                DB::raw("count(tags.id)                  as tags_count"),
                DB::raw("count(tasks.id)                 as tasks_count"),
            ])
            ->withCount(['services', 'machinerySales', 'partsSales'])
            ->leftJoin('dispatcher_customer_contracts as dcc', function ($join) {
                $join->on('dispatcher_customers.id', '=', 'dcc.customer_id');
                $join->where('dcc.customer_type', '=', 'Modules\Dispatcher\Entities\Customer');
            })
            ->leftJoin('dispatcher_leads as dl', function ($join) {
                $join->on('dispatcher_customers.id', '=', 'dl.customer_id');
                $join->where('dl.customer_type', '=', 'Modules\Dispatcher\Entities\Customer');
            })
            ->leftJoin(
                DB::raw("(select count(`orders`.`id`) as ctn,
                      sum(orders.amount) as amount,
                      orders.customer_id   as ocid,
                      orders.customer_type as oct
               from `orders`
               where `orders`.`deleted_at` is null
               group by orders.customer_id, orders.customer_type) o"),
                function ($join) {
                    $join->on('dispatcher_customers.id', '=', 'o.ocid');
                    $join->where('o.oct', '=', 'Modules\Dispatcher\Entities\Customer');
                })
            ->leftJoin(
                DB::raw("(select sum(dispatcher_invoices.paid_sum) as paid_sum,
                       orders.customer_id                          as ocid,
                       orders.customer_type                        as oct
                       from dispatcher_invoices
                                inner join orders on orders.id = dispatcher_invoices.owner_id
                       where owner_type = 'Modules\\\Orders\\\Entities\\\Order'
                         and orders.deleted_at is null
                       group by orders.customer_id, orders.customer_type) invoices"),
                function ($join) {
                    $join->on('dispatcher_customers.id', '=', 'invoices.ocid');
                    $join->where('invoices.oct', '=', 'Modules\Dispatcher\Entities\Customer');
                })
            ->leftJoin(
                DB::raw("(select company_taggables.id,
                    company_taggables.taggable_id   as ctid,
                    company_taggables.taggable_type as ctt
                    from company_tags
                        inner join company_taggables on company_tags.id = company_taggables.company_tag_id
                    group by company_taggables.taggable_id, company_taggables.taggable_type) tags"),
                function ($join) {
                    $join->on('dispatcher_customers.id', '=', 'tags.ctid');
                    $join->where('tags.ctt', '=', 'Modules\Dispatcher\Entities\Customer');
                })
            ->leftJoin(
                DB::raw("(select employee_tasks.id,
                           employee_tasks_binds.bind_id   as etbid,
                           employee_tasks_binds.bind_type as etbt
                    from employee_tasks
                         inner join employee_tasks_binds on employee_tasks.id = employee_tasks_binds.employee_task_id
                    group by employee_tasks_binds.bind_id, employee_tasks_binds.bind_type) tasks"),
                function ($join) {
                    $join->on('dispatcher_customers.id', '=', 'tasks.etbid');
                    $join->where('tasks.etbt', '=', 'Modules\Dispatcher\Entities\Customer');
                })
            ->leftJoin('cities', 'cities.id', '=', 'dispatcher_customers.city_id')
            ->groupBy('dispatcher_customers.id')
            ->withSum('servicesInvoices as services_invoices_sum', 'sum')
            ->withSum('servicesInvoices as services_invoices_paid_sum', 'paid_sum')
            ->with(['tasks', 'tags', 'manager']);

        $filter = new Filter($customers);

        $filter->getEqual([
            'customer_id' => 'dispatcher_customers.id',
            'channel' => 'dispatcher_customers.channel',
            'creator_id'  => 'dispatcher_customers.creator_id',
        ])->getLike([
            'company_name'   => 'company_name',
            'phone'          => 'phone',
            'contact_person' => 'contact_person',
            'source' => 'dispatcher_customers.source',
        ]);

        CustomerService::filter($customers, $request);

        if ($request->filled('has_tasks')) {
            $customers->has('tasks');
        }

        if ($request->filled('contract_number')) {
            $customers->whereHas('contract', function ($q) use
            (
                $request
            ) {

                $q->where('current_number', 'like', "%{$request->contract_number}%");
            });
        }

        if ($request->filled('last_interval_date_from') || $request->filled('last_interval_date_to')) {
            $customers->whereHas('orders.vehicles', function (Builder $q) use
            (
                $request
            ) {
                if ($request->filled('orders_date_from')) {
                    $dt = Carbon::parse($request->orders_date_from)->startOfDay();
                    $q->where('order_workers.date_from', '>=', $dt);
                }
                if ($request->filled('orders_date_to')) {
                    $dt = Carbon::parse($request->orders_date_to)->endOfDay();
                    $q->where('order_workers.date_to', '<=', $dt);
                }
            });
        }

        if ($request->filled('type')) {
            $type = $request->input('type');
            switch ($type) {
                case 'legal':
                    $customers->where('in_black_list', false)
                        ->where(function ($q) {
                            $q->whereHas('entity_requisites')
                                ->orWhereHas('international_legal_requisites');
                        });
                    break;
                case 'individual':
                    $customers->where('in_black_list', false)->whereHas('individual_requisites');
                    break;
                case 'black_list':
                    $customers->where('in_black_list', true);
                    break;
            }
        }

        if ($request->filled('pay_type')) {
            switch ($request->input('pay_type')) {
                case 'paid':
                    $customers->havingRaw('CAST(invoice_pays_sum as signed) >= CAST(orders_sum as signed)');
                    break;
                case 'not_paid':
                    $customers->havingRaw('CAST(invoice_pays_sum as signed) < CAST(orders_sum as signed)');
                    break;
            }
        }

        if ($request->filled('sortBy')) {
            $sort =
                toBool($request->input('sortDesc'))
                    ? 'desc'
                    : 'asc';
            switch ($request->input('sortBy')) {
                case 'phone_number':
                    $customers->orderBy('phone', $sort);
                    break;
                default:
                    $customers->orderBy($request->input('sortBy'), $sort);
            }

        } else {
            $customers->orderBy('dispatcher_customers.created_at', 'desc');
        }

        $collection = CustomersList::collection($customers->paginate($request->per_page
            ?: 15));


        $orders = Order::query()->forBranch()->where('customer_type', Customer::class);
        $orderIds = $orders->pluck('id')->toArray();

        $services = ServiceCenter::query()->where('customer_type', Customer::class);
        $servicesIds = $services->pluck('id')->toArray();

        $paidSumQuery = DispatcherInvoice::query()->where('owner_type', Order::class)
            ->whereIn('owner_id', $orderIds);
        $invoiceSumQuery = DispatcherInvoice::query()->where('owner_type', Order::class)
            ->whereIn('owner_id', $orderIds);


        $paidServicesSumQuery = DispatcherInvoice::query()->where('owner_type', Order::class)
            ->whereIn('owner_id', $servicesIds);
        $invoiceServicesSumQuery = DispatcherInvoice::query()->where('owner_type', Order::class)
            ->whereIn('owner_id', $servicesIds);

        $paidSumQuery = EloquentSerializeFacade::serialize($paidSumQuery);
        $invoiceSumQuery = EloquentSerializeFacade::serialize($invoiceSumQuery);


        try {
            [$paidSum, $invoiceSum] = Octane::concurrently([
                fn() => EloquentSerializeFacade::unserialize($paidSumQuery)->sum('paid_sum'),
                fn() => EloquentSerializeFacade::unserialize($invoiceSumQuery)->sum('sum'),
            ]);
        } catch (\Exception $exception) {
            $paidSum = EloquentSerializeFacade::unserialize($paidSumQuery)->sum('paid_sum');
            $invoiceSum = EloquentSerializeFacade::unserialize($invoiceSumQuery)->sum('sum');
        }
        $collection->additional([
            'sum'         => $orders->sum('amount') + $invoiceServicesSumQuery->get()->sum('prepared_sum'),
            'paid_sum'    => $paidSum + $paidServicesSumQuery->sum('paid_sum'),
            'invoice_sum' => $invoiceSum + $invoiceServicesSumQuery->sum('sum'),
            'count' => Customer::query()->forBranch()->count(),
        ]);

        return $collection;
    }

    /**
     * Store a newly created resource in storage.
     * @param Request $request
     * @return Response
     */
    public function store(CreateCustomerRequest $request)
    {
        $request->validated();

        DB::beginTransaction();

        /** @var Customer $customer */
        $customer = Customer::create([
            'company_name'      => $request->input('company_name'),
            'address'           => $request->input('address'),
            'region_id'         => $request->input('region_id'),
            'city_id'           => $request->input('city_id'),
            'email'             => $request->input('email'),
            'contact_person'    => $request->input('contact_person'),
            'contact_position'  => $request->input('contact_position'),
            'phone'             => $request->input('phone'),
            'creator_id'        => $request->input('creator_id'),
            'domain_id'         => RequestHelper::requestDomain()->id,
            'company_branch_id' => $this->companyBranch->id,
        ]);
        $contacts = $request->input('contacts');
        if (!is_array($contacts)) {
            $contacts = [];
        }
        /* $contacts[] = [
             'contact_person' => $request->input('contact_person'),
             'phones' => [
                 ['phone' => $request->input('phone')],
             ],
             'emails' => [
                 ['email' => $request->input('email')] ,
             ]
         ];*/
        $customer->addContacts($contacts);
        $reqData = explode('_', $request->input('contractor_requisite_id'));
        $req = $this->companyBranch->findRequisiteByType($reqData[1], $reqData[0]);
        $contract = $customer->generateContract($req);

        if ($request->filled('has_requisite')) {
            $requisite = $request->input('requisite');
            $requisite['creator_id'] = Auth::id();
            $requisite['company_branch_id'] = $this->companyBranch->id;

            if ($request->input('has_requisite') === 'legal') {

                $customer->addLegalRequisites($requisite);
            }

            if ($request->filled('requisite.inn') && $this->companyBranch->OneCConnection) {
                $connection = new OneCService($this->companyBranch);
                try {
                    $connection->checkClient($request->input('requisite'), $contract->toArray());

                } catch (\Exception $exception) {

                }
            }

            if (in_array($request->input('has_requisite'), [
                User\IndividualRequisite::TYPE_PERSON,
                User\IndividualRequisite::TYPE_ENTREPRENEUR,
            ])) {

                $customer->addIndividualRequisites($requisite);
            }
            if ($request->filled('contract_id')) {
                $contract->udpate([
                    'current_number' => $request->input('contract_id')
                ]);
            }
        }


        DB::commit();

        return response()->json(CustomerEdit::make($customer));
    }

    function addContacts(
        Request $request,
                $id
    )
    {
        $request->validate(ContactsService::getValidationRules());

        $customer = Customer::forBranch()->forDomain()->findOrFail($id);

        $service = new ContactsService($this->companyBranch);

        if ($request->filled('contacts')) {
            $customer->addContacts($request->input('contacts'));
        } else {
            $contact = $service->createContact($request->all(), $customer);
        }


        return response()->json($contact ?? []);
    }

    function deleteContact(
        Request $request,
                $id,
                $contactId)
    {
        $customer = Customer::forBranch()->forDomain()->findOrFail($id);

        $customer->contacts()->detach($contactId);
    }


    function setTags(
        Request $request,
                $id
    )
    {
        $customer = Customer::forBranch()->forDomain()->findOrFail($id);

        DB::beginTransaction();

        $tags = CompanyTag::createOrGet($request->all(), $customer->company_branch_id);

        $customer->tags()->sync($tags->pluck('id'));

        DB::commit();

        return response()->json();
    }

    /**
     * Show the specified resource.
     * @param int $id
     * @return Response
     */
    public function show($id)
    {
        $customer = Customer::forBranch()->forDomain()->withCount('tasks')
            ->with([
                'tasks' => function ($query) {
                    return $query->where('employee_id', \Auth::id())
                        ->orWhere('responsible_id', \Auth::id());
                }
            ])->findOrFail($id);


        return response()->json(CustomerEdit::make($customer));
    }

    /**
     * Update the specified resource in storage.
     * @param Request $request
     * @param int $id
     * @return Response
     */
    public function update(
        CreateCustomerRequest $request,
                              $id
    )
    {
        $request->validated();

        $customer = Customer::forBranch()->forDomain()->findOrFail($id);

        DB::beginTransaction();

        $customer->update([
            'company_name'     => $request->input('company_name'),
            'address'          => $request->input('address'),
            'region_id'        => $request->input('region_id'),
            'city_id'          => $request->input('city_id'),
            'contact_person'   => $request->input('contact_person'),
            'phone'            => $request->input('phone'),
            'creator_id'       => $request->input('creator_id'),
            'contact_position' => $request->input('contact_position'),
            'email'            => $request->input('email'),
        ]);

        $customer->addContacts($request->input('contacts'));

        $requisite = $request->input('requisite');

        if ($request->input('has_requisite') === 'legal') {

            $requisite['creator_id'] = Auth::id();
            $requisite['company_branch_id'] = $this->companyBranch->id;

            $customer->addLegalRequisites($requisite);
        }


        if (in_array($request->input('has_requisite'), [
            User\IndividualRequisite::TYPE_PERSON,
            User\IndividualRequisite::TYPE_ENTREPRENEUR,
        ])) {
            $requisite['creator_id'] = Auth::id();
            $requisite['company_branch_id'] = $this->companyBranch->id;

            $customer->addIndividualRequisites($requisite);

        }
        if ($request->filled('contract_id')) {
            $reqData = explode('_', $request->input('contractor_requisite_id'));
            $req = $this->companyBranch->findRequisiteByType($reqData[1], $reqData[0]);
            $contract = $customer->generateContract($req);
            $contract->udpate([
                'current_number' => $request->input('contract_id')
            ]);
        }
        DB::commit();

        return response()->json(CustomerEdit::make($customer));
    }

    /**
     * Remove the specified resource from storage.
     * @param int $id
     * @return Response
     */
    public function destroy($id)
    {
        /** @var Customer $customer */
        $customer = Customer::forBranch()->forDomain()->findOrFail($id);

        if (!$customer->orders()->withTrashed()->exists() && !$customer->leads()->withTrashed()->exists()) {
            $customer->forceDelete();
        } else {
            return \response(['errors' => 'Невозможно удалить клиента т.к. у него уже есть заказы.'], 400);
        }

        return response()->json();
    }

    function createHelper()
    {
        $regions = Region::with('cities')->forDomain()->get();

        return response()->json([
            'regions'    => $regions,
            'brands'     => Brand::all(),
            'categories' => Type::all()
        ]);
    }

    function getInfo($id)
    {
        $customer = Customer::forBranch()->forDomain()->withCount('tasks')
            ->with([
                'scorings',
                'tasks' => function ($query) {
                    return $query->where('employee_id', \Auth::id())
                        ->orWhere('responsible_id', \Auth::id());
                }
            ])->findOrFail($id);

        return CustomerInfo::make($customer);
    }


    /**
     * Импорт клиентов из таблицы Excel
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     * @throws \Box\Spout\Common\Exception\IOException
     * @throws \Box\Spout\Common\Exception\UnsupportedTypeException
     * @throws \Box\Spout\Reader\Exception\ReaderNotOpenedException
     */
    function import(Request $request)
    {

        $request->validate([
            'excel' => 'required|mimeTypes:' .
                'application/vnd.ms-office,' .
                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet,' .
                'application/vnd.ms-excel',
        ]);


        /*        $path = $request->file('excel')->store('upload/excel-files');

                $uid = uniqid();
                $tmp_path = Storage::disk()->putFile("import/excel/{$uid}", $request->file('excel'));*/

        $customers = (new FastExcel())->import($request->file('excel'));
        $hasErrors = [];

        DB::beginTransaction();
        foreach ($customers as $key => $row) {

            $row = array_values($row);

            $company = $row[0];

            $phone = trimPhone($row[1]);

            $name = $row[2];

            $email = $row[3];

            $region = $row[4];

            $city = $row[5];

            $address = $row[6];

            $rules = [
                'phone'   => [
                    'required',
                    'digits:' . RequestHelper::requestDomain()->options['phone_digits'],
                    Rule::unique('dispatcher_customers')->where('user_id', Auth::id())
                ],
                'email'   => [
                    'required',
                    'string',
                    'email',
                    'max:255',
                    Rule::unique('dispatcher_customers')->where('user_id', Auth::id())
                ],
                'address' => 'nullable|string|max:255',
                'company' => 'required|string|max:255',
                'name'    => 'required|string|max:255',
            ];


            $errors = Validator::make([
                'email'   => $email,
                'phone'   => $phone,
                'address' => $address,
                'company' => $company,
                'name'    => $name,
            ], $rules)->errors()->all();

            if ($errors) {


                if ($email || $phone || $company) {
                    \Log::info(implode(' ', $errors));
                    $hasErrors[] = $key + 2;
                }

                continue;
            }

            $region_db = Region::query()->where('name', $region)->first();
            $city_db = false;
            if ($region_db) {
                $city_db = $region_db->cities()->where('name', $city)->first();
            }

            Customer::create([
                'company_name'      => $company,
                'email'             => $email,
                'phone'             => $phone,
                'contact_person'    => $name,
                'address'           => $name,
                'region_id'         => $region_db
                    ? $region_db->id
                    : null,
                'city_id'           => $city_db
                    ? $city_db->id
                    : null,
                'creator_id'        => Auth::id(),
                'domain_id'         => RequestHelper::requestDomain()->id,
                'company_branch_id' => $this->companyBranch->id
            ]);
        }
        DB::commit();
        $errors = implode(',', $hasErrors);
        $message =
            $hasErrors
                ? "Импорт завершен. Строки {$errors} небыли загружены. Некорректный формат либо запись уже существует. Проверьте ваш файл."
                : "Импорт успешно завершен.";

        return response()->json(['message' => $message]);
    }

    function getComments($customer_id)
    {
        $customer = Customer::forBranch()->forDomain()->findOrFail($customer_id);

        return $customer->manager_notes;
    }

    function postComment(
        Request $request,
                $customer_id
    )
    {
        $request->validate([
            'text' => 'required|string|max:500'
        ]);
        $customer = Customer::forBranch()->forDomain()->findOrFail($customer_id);

        $comment = $customer->manager_notes()->save(new ManagerNote([
            'text'       => $request->input('text'),
            'color'       => $request->input('color'),
            'manager_id' => Auth::id()
        ]));

        return response()->json($comment);
    }

    function deleteComment(
        $customer_id,
        $comment_id
    )
    {
        $customer = Customer::forBranch()->forDomain()->findOrFail($customer_id);
        $comment = $customer->manager_notes()->findOrFail($comment_id);
        $comment->delete();

        return response()->json();
    }

    function findContractNumberByInn(Request $request)
    {

    }

    /**
     * История звонков по телефонии связанны
     * @param Request $request
     * @param $customer_id
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\Resources\Json\AnonymousResourceCollection
     */
    function callsHistory(
        Request $request,
                $customer_id
    )
    {
        /** @var Customer $customer */
        $customer = Customer::forBranch()->forDomain()->findOrFail($customer_id);

        $phones = $customer->contacts->pluck('phones.phone');
        $phones[] = $customer->phone;

        if (!$this->companyBranch->company->megafonTelephony) {
            return response()->json();
        }
        $history = $this->companyBranch->company->megafonTelephony
            ->calls_history()
            ->whereIn('phone', $phones)
            ->orderBy('created_at', 'desc')
            ->paginate($request->per_page
                ?: 10);

        return TelpehonyHistoryResource::collection($history);
    }


    function setSettings(
        Request $request,
                $customer_id
    )
    {
        /** @var Customer $customer */
        $customer = Customer::forBranch()->forDomain()->findOrFail($customer_id);

        $service = new CustomerService($customer);

        switch ($request->input('type')) {
            case 'contract':
                $request->validate([
                    'prefix'          => 'nullable|string|max:10',
                    'postfix'         => 'nullable|string|max:10',
                    'number'          => 'nullable|max:255',
                    'internal_number' => 'nullable|numeric',
                    'created_at'      => 'required|date',
                    'order_type'      => 'required|in:rent,service',
                    'requisite_instance'      => 'required|string',
                    'last_application_id'      => 'required|numeric|max:9999999|min:' . $customer->lastApplicationId(Customer\CustomerContract::query()->find($request->input('id')))
                ]);
                $service->changeContractSettings($request->all());
                break;
            case 'black-list' :
                $request->validate([
                    'action' => 'required|in:add,remove'
                ]);

                $service->blackList($request->input('action'));
                break;

            case 'application' :
                $request->validate([
                    'last_application_id' => 'required|numeric|max:9999999|min:' . $customer->lastApplicationId()
                ]);

               // $service->updateLastApplicationId($request->input('last_application_id'));
                break;

        }

        return response()->json();
    }

    function removeDuplicate(
        Request $request,
                $id
    )
    {
        /** @var Customer $customer */
        $customer = Customer::forBranch()->forDomain()->findOrFail($id);
        $customer->removeDuplicate();

        return response()->json();
    }

    function getContracts(Request $request)
    {
        $contracts = Customer\CustomerContract::query()
            ->with(['customer.individual_requisites',
                'orders',
                'services',
                'sales',
                ])
            ->whereHas('customer', fn(Builder $builder) => $builder->forBranch())
            ->orderBy('created_at', 'desc');

        $filter = new Filter($contracts);

        $filter->getEqual([
            'subject_type' => 'subject_type',
            'type' => 'type',
            'customer_id' => 'customer_id',
        ])->getEqual([
            'is_active' => 'is_active'
        ], true);

        if($request->filled('requisite')) {
            $reqData = explode('_', $request->input('requisite'));
            if ($req = $this->companyBranch->findRequisiteByType($reqData[1], $reqData[0])) {
                $contracts->whereMorphRelation(
                    'requisite', [get_class($req)], 'id', $req->id
                );
            }
        }

        return CustomerContractResource::collection($contracts
            ->paginate($request->input('per_page')));
    }

    public function deleteContract($id)
    {
        Customer\CustomerContract::query()->whereHas('customer', fn(Builder $builder) => $builder->forBranch())
            ->findOrFail($id)->delete();
    }
}
