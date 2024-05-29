<?php

namespace Modules\ContractorOffice\Http\Controllers;

use AnourValar\EloquentSerialize\Facades\EloquentSerializeFacade;
use App\Directories\LeadRejectReason;
use App\Events\OrderCreatedEvent;
use App\Events\OrderUpdatedEvent;
use App\Helpers\RequestHelper;
use App\Http\Controllers\Avito\Events\OrderChangedEvent;
use App\Http\Controllers\Avito\Models\AvitoOrder;
use App\Machinery;
use App\Machines\Type;
use App\Service\Google\CalendarService;
use App\Service\RequestBranch;
use App\User\EntityRequisite;
use App\User\IndividualRequisite;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Laravel\Octane\Facades\Octane;
use Modules\AdminOffice\Entities\Filter;
use Modules\CompanyOffice\Entities\Company\DocumentsPack;
use Modules\CompanyOffice\Entities\Company\GoogleCalendar;
use Modules\CompanyOffice\Services\CompanyRoles;
use Modules\ContractorOffice\Entities\CompanyWorker;
use Modules\ContractorOffice\Entities\Sets\MachinerySet;
use Modules\ContractorOffice\Entities\Vehicle\Price;
use Modules\ContractorOffice\Services\Tariffs\TimeCalculation;
use Modules\ContractorOffice\Transformers\OrdersCollection;
use Modules\Dispatcher\Entities\Customer;
use Modules\Dispatcher\Entities\DispatcherInvoice;
use Modules\Dispatcher\Entities\InvoiceItem;
use Modules\Dispatcher\Entities\Lead;
use Modules\Dispatcher\Http\Controllers\InvoiceController;
use Modules\Dispatcher\Http\Requests\FastOrderRequest;
use Modules\Dispatcher\Services\LeadService;
use Modules\Integrations\Rules\Coordinates;
use Modules\Integrations\Services\OneC\OneCService;
use Modules\Integrations\Services\Telegram\TelegramService;
use Modules\Orders\Entities\ContractorFeedback;
use Modules\Orders\Entities\MachinerySetsOrder;
use Modules\Orders\Entities\MachineryStamp;
use Modules\Orders\Entities\Order;
use Modules\Orders\Entities\OrderComponent;
use Modules\Orders\Entities\OrderComponentActual;
use Modules\Orders\Entities\OrderComponentIdle;
use Modules\Orders\Entities\OrderComponentService;
use Modules\Orders\Entities\Payments\InvoicePay;
use Modules\Orders\Entities\Service\ServiceCenter;
use Modules\Orders\Services\AvitoPayService;
use Modules\Orders\Services\OrderDocumentService;
use Modules\Orders\Services\OrderService;
use Modules\ContractorOffice\Services\OrderService as OrderServiceContractor;
use Modules\PartsWarehouse\Entities\Posting;
use Modules\PartsWarehouse\Entities\Stock\Item;
use Modules\PartsWarehouse\Entities\Stock\ItemSerial;
use Modules\PartsWarehouse\Entities\Stock\Stock;
use Modules\PartsWarehouse\Entities\Warehouse\CompanyBranchWarehousePart;
use Modules\PartsWarehouse\Entities\Warehouse\WarehousePartSet;
use Modules\PartsWarehouse\Entities\Warehouse\WarehousePartsOperation;
use function Clue\StreamFilter\fun;

class OrdersController extends Controller
{

    private $companyBranch;

    public function __construct(
        Request       $request,
        RequestBranch $companyBranch)
    {
        $this->companyBranch = $companyBranch->companyBranch;
        $block = $this->companyBranch->getBlockName(CompanyRoles::BRANCH_ORDERS);
        $this->middleware("accessCheck:{$block}," . CompanyRoles::ACTION_SHOW)->only([
            'filterOrders',
            'dispatcherFilter',
            'getOrders',
            'contractorFeedback',
            'getDocuments',

        ]);
        $this->middleware("accessCheck:{$block}," . CompanyRoles::ACTION_CREATE)->only([
            'store', 'update', 'uploadDoc',
            'prolongation', 'addMachineryTimestamp', 'idle', 'changeCustomer', 'getActualHours',
            'saveActualApplication', 'deleteActualApplication', 'upgradeSets'
        ]);
        $this->middleware("accessCheck:{$block}," . CompanyRoles::ACTION_DELETE)->only(['destroy', 'closeLead']);

    }


    function filterOrders(
        Request $request,
        Builder $orders
    )
    {

        if (toBool($request->input('overdue'))) {
            $orders->whereHas('components', function ($q) {
                $q->where('status', Order::STATUS_ACCEPT)
                    ->where('worker_type', Machinery::class)
                    ->where('date_to', '<', now());
            });
            /*             $orders->withCount(['vehicle_timestamps as doneStatusCount' => function($q) {
                                 $q->where('type', 'done');
                             }, 'vehicle_timestamps'])
                             ->whereHas('components', function ($q) {
                                 $q->where('status', Order::STATUS_ACCEPT)
                                     ->whereDate('date_to', '<', now());
                             })
                             ->havingRaw('doneStatusCount < vehicle_timestamps_count');*/
        }
        if ($request->anyFilled(['last_interval_date_from', 'last_interval_date_to'])) {
            $orders->whereHas('components', function ($q) use (
                $request
            ) {
                if ($request->filled('last_interval_date_from') && !$request->filled('last_interval_date_to')) {

                    $df =
                        Carbon::parse($request->input('last_interval_date_from'))->startOfDay()->setTimezone(config('app.timezone'));
                    $q->whereBetween('date_from', [$df, $df->clone()->endOfDay()]);
                    //$q->havingRaw('MAX(`date_to`) >= ?', []);

                } else if ($request->filled('last_interval_date_to') && !$request->filled('last_interval_date_from')) {
                    $dt =
                        Carbon::parse($request->input('last_interval_date_to'))->startOfDay()->setTimezone(config('app.timezone'));

                    $q->whereBetween('date_to', [$dt, $dt->clone()->endOfDay()]);
                    // $q->havingRaw('MAX(`date_to`) <= ?', [Carbon::parse($request->input('last_interval_date_to'))->endOfDay()->setTimezone(config('app.timezone'))]);

                }
                if ($request->filled('last_interval_date_from') && $request->filled('last_interval_date_to')) {
                    $df =
                        Carbon::parse($request->input('last_interval_date_from'))->startOfDay()->setTimezone(config('app.timezone'));

                    $dt =
                        Carbon::parse($request->input('last_interval_date_to'))->startOfDay()->setTimezone(config('app.timezone'));

                    $q->forPeriod($df, $dt);
                }
            });
        }

        if ($request->filled('sortBy')) {
            $sort = $request->input('sortBy');
            $type =
                toBool($request->input('asc'))
                    ? 'asc'
                    : 'desc';
            $orders->withCount([
                'components as end_date' => function ($query) {
                    $query->select(DB::raw("max(order_workers.date_to)"));
                }
            ]);
            switch ($sort) {
                case 'start_date':
                    $orders->orderBy('date_from', $type);
                    break;
                case 'end_date':
                    $orders->orderBy('end_date', $type);
                    break;
            }
        } else {
            $orders->orderBy('created_at', 'desc');
        }

        $errors = Validator::make($request->all(), [
            'category_id' => 'nullable|exists:types,id',
            'name' => 'nullable|string|max:255'
        ])
            ->errors()
            ->getMessages();
        if ($errors) {
            return;
        }
        $filter = new Filter($orders);

        if (!$request->filled('archive')) {
            if (is_array($request->input('status'))) {
                $orders->whereIn('status', $request->input('status'));
            }
        }
        $filter->getEqual([
            'customer_id' => 'customer_id',
            'creator_id' => 'creator_id',
        ])->getLike([
            // 'internal_number' => 'internal_number',
            'address' => 'address',
            'comment' => 'comment',
        ]);
        $filterPhone = trimPhone($request->input('phone'));
        if ($filterPhone !== '') {
            $orders->where(function (Builder $builder) use ($filterPhone) {
                return $builder->filterContactPerson(null, (int)$filterPhone);
            });
        }

        if ($request->anyFilled(['category_id', 'brand_id', 'model_id'])) {
            $orders->whereHas('vehicles', function ($q) use (
                $request
            ) {
                $q
                    ->when($request->category_id, fn($b) => $b->whereType($request->category_id))
                    ->when($request->brand_id, fn($b) => $b->whereBrandId($request->brand_id))
                    ->when($request->model_id, fn($b) => $b->whereModelId($request->model_id))
                    ->forBranch();
            });

        }
        if ($request->filled('machinery_set_id')) {
            $orders->whereHas('machinerySets', function ($q) use (
                $request
            ) {
                $q->whereHas('machinerySet', function ($q2) use (
                    $request
                ) {
                    $q2->where('id', $request->machinery_set_id)->forBranch();
                });
            });
        }
        if ($request->filled('item_id')) {
            $orders->whereHas('workers', function ($q) use ($request) {
                $q->whereHasMorph('worker', [WarehousePartSet::class], function ($q) use ($request) {
                    return $q->whereHas('parts', function ($q) use ($request) {
                        return $q->where('part_id', $request->item_id);
                    });
                })->orWhereHas('parts', function ($q) use ($request) {
                    return $q->where('part_id', $request->item_id);
                });
            });
        }
        if ($request->filled('vehicle_id')) {
            $orders->whereHas('vehicles', function ($q) use (
                $request
            ) {
                $q->where('machineries.id', $request->vehicle_id)->forBranch();
            });
        }

        if ($request->filled('date')) {
            $date = Carbon::parse($request->input('date'));
            $orders->forPeriod($date->copy(), $date->copy());
        }

        if ($request->filled('name')) {
            $orders->where(function ($q) use (
                $request
            ) {
                $q->whereHas('vehicles', function ($q) use (
                    $request
                ) {
                    $q->where('name', 'like', "%{$request->name}%")
                        ->forBranch();
                });
            });
        }

        if ($request->filled('driver_type')) {
            if ($request->input('driver_type') === 'with_driver') {
                $orders->whereHas('components', function ($q) {
                    $q->whereHas('driver');
                });
            }

            if ($request->input('driver_type') === 'has_sub') {
                $orders->whereHas('components', function ($q) {
                    $q->whereHasMorph('worker', [Machinery::class], function ($q) {
                        $q->whereNotNull('sub_owner_type');
                    });
                });
            }
            if ($request->input('driver_type') === 'has_sets') {
                $orders->whereHas('machinerySets');
            }

            if ($request->input('driver_type') === 'has_services') {
                $orders->whereHas('components', function ($q) {
                    $q->where('cost_per_unit', 0);
                    $q->whereHas('services');
                });
            }
            if ($request->input('driver_type') === 'has_parts') {
                $orders->where(function ($orders) {
                    $orders->whereHas('components', function ($q) {
                        $q->whereHas('parts')->orWhere('worker_type', WarehousePartSet::class);
                    });
                });

            }
            if ($request->input('driver_type') === 'without_driver') {
                $orders->where(function (Builder $q) {
                    $q->whereHas('components', function ($q) {
                        $q->whereDoesntHave('driver');
                    })->orWhere('worker_type', WarehousePartSet::class);
                });

            }


        }

    }

    private function dispatcherFilter(
        Request $request,
        Builder $orders
    )
    {

        if ($request->input('contract_number')) {
            $orders->whereRelation('contract', 'number', 'like', "%{$request->input('contract_number')}%");
        }

        if ($request->filled('requisite')) {
            $reqData = explode('_', $request->input('requisite'));
            if ($req = $this->companyBranch->findRequisiteByType($reqData[1], $reqData[0])) {
                $orders->whereRelation('contract', 'requisite_id', $req->id);
                $orders->whereRelation('contract', 'requisite_type', get_class($req));
            }
        }

        if (toBool($request->input('tb_customer', false))) {

            $orders->whereDoesntHave('leads');
        }

        if (toBool($request->input('my_customer', false))) {
            $orders->whereHas('leads', function ($q) {
                $q->whereHas('customer', function ($q) {
                    $q->forBranch();
                });
            });
        }

        if (toBool($request->input('black_invoice'))) {
            $orders->whereHas('invoices', function ($q) {
                $q->where('is_black', 1);
            });
        }

        if (toBool($request->input('dispatcher_customer', false))) {
            $orders->whereHas('leads', function ($q) {
                $q->whereHas('customer', function ($q) {
                    $q->where('dispatcher_customers.company_branch_id', '!=', $this->companyBranch->id);
                });
            });
        }

        if ($request->filled('pay_type')) {

            switch ($request->input('pay_type')) {
                case 'paid':

                    /*    $orders->withCount(['invoices as total_paid'=> function (Builder $q) {
                            $q->selectRaw('SUM(paid_sum)');
                           //$q->whereRaw('SUM(`dispatcher_invoices`.`paid_sum`) >= `amount`');
                        }])->havingRaw('total_paid >= orders.amount');*/
                    $orders->whereHas('invoices', function (Builder $q) {
                        $q->havingRaw('SUM(paid_sum) >= `orders`.`amount`');
                        //$q->whereRaw('SUM(`dispatcher_invoices`.`paid_sum`) >= `amount`');
                    });
                    break;
                case 'partial':
                case 'not_paid':
                    $orders->where(function ($orders) {
                        $orders->whereHas('invoices', function (Builder $q) {
                            $q->havingRaw('SUM(paid_sum) < `orders`.`amount`');
                            //$q->whereRaw('SUM(`dispatcher_invoices`.`paid_sum`) >= `amount`');
                        })->orWhereDoesntHave('invoices');
                    })->whereNotIn('status', [Order::STATUS_REJECT, Order::STATUS_CLOSE]);

                    break;
                /*  case 'not_paid':
                      $orders->has('pays', '=', 0);
                      break;*/
            }
        }
    }


    function getOrders(
        Request $request,
                $id = null
    )
    {
        $stampTable = (new MachineryStamp)->getTable();
        /** @var Builder $orders */
        $orders = Order::query()->forBranch()->withCount('tasks')
            //    ->crossJoin("{$stampTable} as order_timestamps", 'order_id', '=' ,'orders.id')
            ->with([
                'contract',
                'tasks' => function ($query) {
                    $query->where('employee_id', \Auth::id())
                        ->orWhere('responsible_id', \Auth::id());
                },
                'components' => function ($q) {
                    $q->withSum('contractorPays as contractor_pays_sum', 'sum');
                },
                'components.services.customService',
                'components.worker.subOwner',
                'components.worker.order_timestamps',
                'components.contractorPays',
                'components.machineryBase',
                'components.actual',
                'base',
                'machinerySets',
                'creator',
                'manager',
                'depositTransfer',
                'domain.currencies',
                'company_branch',
                'leads',
                'categories', 'customer', 'contacts', 'dispatcher_contractors', 'invoices.pays'
            ])
            ->contractorOrders();

        if ($request->has('ids')) {
            return \Modules\ContractorOffice\Transformers\Order::collection(
                $orders->whereIn('id', explode(',', $request->input('ids', '')))->get()
            );
        }
        if ($id) {
            $orders->with([
                'leads', 'vehicles' => function ($q) use (
                    $id
                ) {
                    $q->forBranch();
                }, 'components.actual.reports'
            ]);
            return \Modules\ContractorOffice\Transformers\Order::make($orders->findOrFail($id));
        }
        // $orders->with([
        //     'workers' => function ($q) {
        //         $q->without(['worker', 'histories', 'idle_periods', 'driver', 'media', 'services']);
        //     }
        // ]);

        $orders->filter($request->all());
        $this->filterOrders($request, $orders);
        $this->dispatcherFilter($request, $orders);

        if ($request->boolean('kanban')) {
            return OrdersCollection::collection($orders->get());
        }
        $ids = $orders->pluck('id');
        $paginator = $orders->paginate($request->per_page
            ?: 5);
        $collection =
            OrdersCollection::collection($paginator);

        $invoices = DispatcherInvoice::query()->where('owner_type', Order::class)
            ->whereIn('owner_id', $ids);


        $paidSum = $invoices->get()->sum('paid_sum');
        $invoiceSum = $orders->sum('amount');

        $collection->additional([
            'sum' => $orders->sum('amount'),
            'paid_sum' => $paidSum,
            'invoice_sum' => $invoiceSum,
            'total_value_added' => OrderComponent::query()->whereIn('order_id', $ids)->sum('value_added') + OrderComponentService::query()
                    ->whereHas('orderComponent', fn(Builder $orderComponentBuilder) => $orderComponentBuilder->whereIn('order_id', $ids))->sum('value_added'),
            'fact' =>
                (OrderComponentActual::query()->whereHas('orderComponent', function (Builder $q) use ($ids) {
                        $q->whereIn('order_id', $ids);
                    })
                        ->get()
                        ->sum('amount')
                    + OrderComponent::query()
                        ->whereDoesntHave('actual')
                        ->whereIn('order_id', $ids)
                        ->get()
                        ->sum('amount')
                ),
            'total_value_added_fact' => OrderComponentActual::query()->whereHas('orderComponent', function (Builder $q) use ($ids) {
                    $q->whereIn('order_id', $ids);
                })->get()->sum(fn($item) => $item->value_added * $item->order_duration + $item->services->sum('value_added'))
                + OrderComponent::query()->whereDoesntHave('actual')->whereIn('order_id', $ids)->get()->sum(fn($item) => $item->value_added * $item->order_duration + $item->services->sum('value_added'))
        ]);
        return $collection;
    }


    function addMachineryTimestamp(
        Request $request,
                $order_id
    )
    {
        $arr = [
            'status' => 'required|in:on_the_way,arrival,done',
            'date' => 'required|date',
            'time' => 'required|date_format:H:i',
        ];

        if (request()->has('coordinates')) {
            $arr = array_merge($arr, [
                'coordinates' => new Coordinates
            ]);
        }

        $request->validate($arr);

        $order = Order::with('payment', 'user')
            ->contractorOrders()
            // ->whereStatus(Order::STATUS_ACCEPT)
            ->findOrFail($order_id);

        $position = $order->components()->findOrFail($request->input('position_id'));

        $result =
            $order->addMachineryCoordinates($position->worker_id, $request->status,
                Carbon::parse("{$request->input('date')} {$request->input('time')}"));
        /*    if ($result && $request->status === 'done') {
                (new EventNotifications())->contractorDoneOrder($order, Auth::user());
            }*/
        return (string)$result;
    }


    function createFastOrder(FastOrderRequest $request, CalendarService $service)
    {
        $leadService = new LeadService();

        if ($request->filled('customer_id') && !$request->filled('contract_id') && !$request->input('contract.subject_type')) {
            throw ValidationException::withMessages(['errors' => "Не выбран договор."]);
        }
        DB::beginTransaction();
        try {
            $reqData = explode('_', $request->input('contractor_requisite_id'));
            $req = $this->companyBranch->findRequisiteByType($reqData[1], $reqData[0]);
            if ($request->filled('customer_id')) {
                $customer = Customer::forCompany()->findOrFail($request->input('customer_id'));
                if ($request->input('contract_id')) {
                    $contract = Customer\CustomerContract::query()->findOrFail($request->input('contract_id'));
                } else {
                    $contract = $customer->generateNewContract($req, $request->input('contract', []));
                }
            } else {
                $customer = Customer::create([
                    'email' => $request->input('email'),
                    'company_name' => $request->input('customer.company_name'),
                    'region_id' => $request->input('customer.region_id'),
                    'city_id' => $request->input('customer.city_id'),
                    'contact_person' => $request->input('contact_person'),
                    'phone' => $request->input('phone'),
                    'creator_id' => Auth::id(),
                    'company_branch_id' => $this->companyBranch->id,
                    'domain_id' => RequestHelper::requestDomain()->id,
                    'source' => $request->input('source'),
                    'channel' => $request->input('channel'),
                ]);

                $requisite = $request->input('customer.requisite');
                $requisite['creator_id'] = Auth::id();
                $requisite['company_branch_id'] = $this->companyBranch->id;

                $contract = $customer->generateContract($req, $request->input('contract', []));


                if ($request->input('has_requisite') === 'legal') {

                    $customer->addLegalRequisites($requisite, $contract->toArray());
                }

                if (in_array($request->input('has_requisite'), [
                    IndividualRequisite::TYPE_PERSON,
                    IndividualRequisite::TYPE_ENTREPRENEUR,
                ])) {

                    $customer->addIndividualRequisites($requisite);
                }

                if ($request->filled('customer.requisite.inn') && $this->companyBranch->OneCConnection) {
                    $connection = new OneCService($this->companyBranch);
                    try {
                        $connection->checkClient($requisite, $contract->toArray());

                    } catch (\Exception $exception) {

                    }
                }
            }
            //^^^DONE

            $data = $request->all();
            if (!empty($data['sets'])) {
                foreach ($data['sets'] as $set) {

                    foreach ($set['vehicles'] as $vehicleArr) {
                        foreach ($vehicleArr as $vehicle) {
                            $data['vehicles'][] = $vehicle;
                        }
                    }
                }
            }
            $it = 0;
            $oldChangeHours = [];
            foreach ($data['vehicles'] as $i => $vehicle) {
                $v = Machinery::query()->forBranch()->findOrFail($vehicle['id']);
                $oldChangeHours[$v->id] = $v->change_hour;
                if (!empty($vehicle['shift_duration']) && in_array($vehicle['shift_duration'], [8, 23, 24])) {
                    $v->update(['change_hour' => $vehicle['shift_duration']]);
                }

                $data['vehicles_categories'][$i] = $vehicle;

                $data['vehicles_categories'][$i]['start_time'] = Carbon::parse($vehicle['date_from'])->format('H:i');
                $data['vehicles_categories'][$i]['count'] = 1;
                $data['vehicles_categories'][$i]['type_id'] = $v->type;
                $data['vehicles_categories'][$i]['id'] = $v->type;
                $data['vehicles_categories'][$i]['machinery_model_id'] = $v->model_id;
                $data['vehicles_categories'][$i]['optional_attributes'] = $vehicle['optional_attributes'] ?? null;
                $it++;

            }

            if (isset($data['warehouse_sets'])) {
                foreach ($data['warehouse_sets'] as $i => $warehouseSet) {
                    $ii = $i + $it;
                    $w = WarehousePartSet::query()->updateOrCreate(
                        [
                            'id' => isset($warehouseSet['id'])
                                ? $warehouseSet['id']
                                : null
                        ],
                        [
                            'type_id' => Type::query()->where('alias', 'parts')->first()->id,
                            'company_branch_id' => $this->companyBranch->id,
                            'machinery_base_id' => $request->input('machinery_base_id')
                        ]
                    );
                    $w->name = "Набор запчастей " . $w->id;
                    $w->save();
                    $data['vehicles_categories'][$ii] = $warehouseSet;

                    $data['vehicles_categories'][$ii]['start_time'] =
                        Carbon::parse($warehouseSet['date_from'])->format('H:i');
                    $data['vehicles_categories'][$ii]['count'] = 1;
                    $data['vehicles_categories'][$ii]['type_id'] = $w->type_id;
                    $data['vehicles_categories'][$ii]['id'] = $w->type_id;
                    $data['vehicles_categories'][$ii]['machinery_model_id'] = null;
                    $data['vehicles_categories'][$ii]['warehouse_part_set_id'] = $w->id;
                }
            }
            $data['is_fast_order'] = 1;
            $leadService->setSource($request->input('source'));
            $leadService
                ->setDispatcherCustomer($customer)
                ->createNewLead($data, $this->companyBranch->id, $request->input('creator_id')
                    ?: Auth::id());

            $vehicles = $data['vehicles'];
            if (isset($data['warehouse_sets'])) {
                foreach ($data['warehouse_sets'] as $i => $warehouseSet) {
                    $vehicles[] = $warehouseSet;
                }
            }
            /** @var Lead $lead */
            $lead = $leadService->getLead();
            foreach ($lead->positions()->orderBy('id', 'asc')->get() as $k => $position) {

                $vehicles[$k]['position_id'] = $position->id;
                $vehicles[$k]['order_type'] = $vehicles[$k]['driver_type'];
            }

            /** @var Order $order */
            $order = $lead->createMyOrder($vehicles, $lead->contractorRequisite, $contract ?? null);

            $order->external_id = $request->input('external_id');

            if (!empty($data['sets'])) {
                foreach ($data['sets'] as $set) {
                    $findSet = MachinerySet::query()->forBranch()->findOrFail($set['id']);
                    $deliveryCost = 0;
                    $returnDelivery = 0;
                    foreach ($set['vehicles'] as $vArray) {
                        $vehCollection = collect($vArray);
                        $deliveryCost += numberToPenny($vehCollection->sum('delivery_cost'));
                        $returnDelivery += numberToPenny($vehCollection->sum('return_delivery'));
                    }


                    $machinerySetOrder = MachinerySetsOrder::create([
                        'order_id' => $order->id,
                        'machinery_set_id' => $findSet->id,
                        'prices' => [
                            'sum' => $order->isVatSystem()
                                ? Price::addVat($set['sum'], $order->company_branch->domain->country->vat)
                                : $set['sum'],
                            'delivery_cost' => $order->isVatSystem()
                                ? Price::addVat($deliveryCost, $order->company_branch->domain->country->vat)
                                : $deliveryCost,
                            'return_delivery' => $order->isVatSystem()
                                ? Price::addVat($returnDelivery, $order->company_branch->domain->country->vat)
                                : $returnDelivery
                        ],
                        'count' => count($set['vehicles']),
                    ]);

                    foreach ($set['vehicles'] as $vehicleArr) {
                        foreach ($vehicleArr as $vehicle) {
                            $component = $order->components->where('worker_id', $vehicle['id'])->first();
                            $component->update([
                                'machinery_sets_order_id' => $machinerySetOrder->id
                            ]);
                        }
                    }
                }
            }
            $order->contract()->associate($contract);
            $order->machinery_base_id = $request->input('machinery_base_id');
            $order->bank_requisite_id = $request->input('bank_requisite_id');
            $order->comment = $request->input('comment');
            $order->principal_id = $request->input('principal_id');
            $order->save();
            if ($order->principal) {
                $order->contacts()->attach($order->principal->person->id);
            }
            if (is_array($request->input('contacts'))) {
                foreach ($request->input('contacts') as $contact) {
                    if (!empty($contact['id'])) {
                        $order->contacts()->syncWithoutDetaching($contact['id']);
                    }
                }
            }
            foreach ($oldChangeHours as $vId => $hour) {
                Machinery::query()->where('id', $vId)->update([
                    'change_hour' => $hour
                ]);
            }
            $order->getContractUrl();
            DB::commit();

        } catch (\Exception $exception) {
            logger($exception->getMessage() . ' ' . $exception->getTraceAsString());
            DB::rollBack();

            return response()->json($exception->getMessage(), 400);
        }

        $tgService = new TelegramService();
        foreach ($order->workers()->whereHas('driver')->get() as $component) {
            $tgService->sendOrderInfoToDriver($component);
        }

        OrderCreatedEvent::dispatch($order);

        return response()->json([
            'id' => $order->id
        ]);
    }

    function getActualHours(
        Request $request,
                $id
    )
    {
        $order = Order::contractorOrders()->findOrFail($id);

        $service = new OrderService();
        $service->setOrder($order);

        return response()->json($service->getApplicationWorkHours($request->input('application_id')));

    }


    function contractorFeedback(
        Request $request,
                $id
    )
    {
        $order = Order::whereDoesntHave('contractor_feedback', function ($q) {
            $q->currentUser();
        })
            ->findOrFail($id);

        $errors = Validator::make($request->all(), [
            'content' => 'required|string|min:5|max:255'
        ])->errors()->getMessages();

        if ($errors) {
            return response()->json($errors, 419);
        }

        DB::beginTransaction();

        $feedback = ContractorFeedback::create([
            'content' => $request->input('content'),
            'order_id' => $order->id,
            'user_id' => Auth::id()
        ]);

        DB::commit();


        return $feedback;
    }

    function uploadDoc(
        Request $request,
                $id
    )
    {
        $order = Order::contractorOrders()->findOrFail($id);

        $request->validate([
            'name' => 'required|string|max:255',
            'doc' => 'required|string|max:255',
        ]);
        $tmp_dir = config('app.upload_tmp_dir');

        $tmp_file_path = "{$tmp_dir}/{$request->input('doc')}";

        $exists = Storage::disk('tmp')->exists($request->input('doc'));

        if (!$exists) {
            return response()->json(['doc' => ['Файл не найден. Попробуйте еще раз.']], 400);
        }


        return $order->addDocument($request->input('name'), $tmp_file_path);
    }

    function getDocuments($id)
    {
        $order = Order::contractorOrders()->findOrFail($id);

        return $order->documents()->with('user')->get();
    }

    function prolongation(
        Request $request,
                $id
    )
    {
        $request->validate([
            'duration' => 'required|numeric|min:1|max:999',
            'cost_per_unit' => ($request->input('type') === 'prolongation'
                    ? 'nullable'
                    : 'required') . '|numeric|min:0|max:9999999',
            'value_added' => 'nullable|numeric|min:0|max:9999999',
            'position_id' => 'required|exists:order_workers,id',
            'type' => 'required|in:prolongation,new',
            'shift_duration' => 'nullable|integer|min:0|max:24',
            'order_type' => ($request->input('type') === 'new'
                    ? 'required'
                    : 'nullable') . '|in:shift,hour'
        ]);
        $order = Order::contractorOrders()->findOrFail($id);

        $service = new OrderService();
        if ($request->get('all')) {
            foreach ($order->components as $component) {
                DB::beginTransaction();
                if ((int)$request->shift_duration && $component->worker instanceof Machinery) {
                    $component->worker->update([
                        'change_hour' => $request->input('shift_duration')
                    ]);
                }
                $service->setOrder($order)
                    ->prolongation(
                        $component->id,
                        $request->input('duration'),
                        numberToPenny($request->input('cost_per_unit')),
                        $request->input('type') === 'new',
                        $request->input('order_type'),
                        $request->input('value_added'),
                        $request->input('start_date'),
                        $request->input('time_from')
                    );

                DB::commit();
            }
        } else {
            DB::beginTransaction();
            $position = $order->components()->findOrFail($request->input('position_id'));
            if ((int)$request->shift_duration && $position->worker instanceof Machinery) {
//                $position->worker->update([
//                    'change_hour' => $request->input('shift_duration')
//                ]);
            }
            $service->setOrder($order)
                ->prolongation(
                    $request->input('position_id'),
                    $request->input('duration'),
                    numberToPenny($request->input('cost_per_unit')),
                    $request->input('type') === 'new',
                    $request->input('order_type'),
                    $request->input('value_added'),
                    $request->input('shift_duration'),
                    $request->input('start_date'),
                    $request->input('time_from')
                );

            DB::commit();
        }
        OrderUpdatedEvent::dispatch($order);
        return response()->json();
    }

    function idle(
        Request $request,
                $id
    )
    {
        $order = Order::contractorOrders()->findOrFail($id);

        $request->validate([
            'duration' => 'required|numeric|min:1',
            'position_id' => 'required|numeric',
            'type' => 'required|integer'
        ]);
        $service = new OrderService();
        $service->setOrder($order);

        DB::beginTransaction();

        /** @var OrderComponent $position */
        $position = OrderComponent::query()->findOrFail($request->input('position_id'));

        $request->merge([
            'shift_duration' => $position->shift_duration
        ]);
        if($position->shift_duration === 24 && $position->order_type === 'shift'){
            $dateFrom = Carbon::parse($position->date_to)->addMinutes();
        }else {
            $dateFrom = Carbon::parse($position->date_to)->addDay()->setTimeFrom(Carbon::parse($position->date_from));
        }
        $duration = $request->input('duration');

        $service->prolongation(
            $position->id,
            $duration,
            ($request->input('type') === OrderComponentIdle::TYPE_CONTRACTOR) ? 0 : $position->cost_per_unit,
            true,
            $position->order_type,
            0,
            $position->shift_duration,
            $dateFrom->format('Y-m-d'),
            $dateFrom->format('H:i')
        );
        $idlePosition = $order->fresh()->components()->latest()->first();

        $idlePosition->update([
            'description' => $idlePosition->description.' (Простой)'
        ]);

        $dateTo = Carbon::parse($idlePosition->date_to);
        $service->setIdle(
            $idlePosition->id,
            $dateFrom,
            $dateTo,
            $request->input('type')
        );

        DB::commit();

        return response()->json();
    }

    function changePrincipal(
        Request $request,
                $id)
    {
        $request->validate([
            'principal_id' => 'nullable|exists:principal_docs,id',
        ]);

        $order = Order::forBranch()->findOrFail($id);

        $order->update([
            'principal_id' => $request->input('principal_id')
        ]);
    }

    function changeContract(Request $request, $id)
    {
        $request->validate(['contract_id' => 'required']);

        $order = Order::forBranch()->findOrFail($id);
        $contract = $order->customer->contracts()->findOrFail($request->input('contract_id'));

        DB::beginTransaction();

        $order->contract()->associate($contract);
        $order->save();

        DB::commit();

        return response()->json();
    }

    function changeDriver(
        Request $request,
                $id
    )
    {
        $request->validate([
            'driver_id' => 'nullable|integer',
        ]);
        $order = Order::forBranch()->findOrFail($id);
        $order->update([
            'driver_id' => $request->driver_id
        ]);
    }

    function avitoSync(Request $request, Order $order)
    {
        OrderChangedEvent::dispatch($order, AvitoOrder::STATUS_PREPAID);
    }

    function changeContractor(
        Request $request,
                $id
    )
    {
        $request->validate([
            'contractor_requisite_id' => 'required|string',
            'documents_pack_id' => 'required|exists:company_documents_packs,id',
            'contract_id' => 'nullable',
        ]);
        $order = Order::forBranch()->findOrFail($id);
        $virtualId = config('avito.virtual_company_id');
//
        if (!$order->isAvitoOrder() && $order->invoices()->exists()) {
            throw ValidationException::withMessages([
                'errors' => 'В сделке уже выставлены счета.'
            ]);
        } else if ($order->company_branch_id != $virtualId && $order->invoices()->exists()) {
            throw ValidationException::withMessages([
                'errors' => 'В сделке уже выставлены счета.'
            ]);
        }
        $reqData = explode('_', $request->input('contractor_requisite_id'));
        $req = $this->companyBranch->findRequisiteByType($reqData[1], $reqData[0]);
        $contract = $request->filled('contract_id') ?
            $order->customer->contracts()->findOrFail($request->input('contract_id'))
            : $order->customer->generateContract($req);
        $order->contractorRequisite()->associate($req);
        $order->contract()->associate($contract);
        $order->save();
    }

    function changeCustomer(
        Request $request,
                $id
    )
    {
        $request->validate([
            'customer_id' => 'required|exists:dispatcher_customers,id',
            'documents_pack_id' => 'required|exists:company_documents_packs,id',
        ]);
        $order = Order::forBranch()->findOrFail($id);

        // if ($order->invoices()->exists()) {
        //     throw ValidationException::withMessages([
        //         'errors' => 'В сделке уже выставлены счета.'
        //     ]);
        // }
        $pack = DocumentsPack::query()->forBranch()->findOrFail($request->input('documents_pack_id'));
        $customer = Customer::query()->forBranch()->findOrFail($request->input('customer_id'));

        DB::beginTransaction();

        $order->lead->documentsPack()->associate($pack);
        $order->lead->customer_id = $request->input('customer_id');
        $order->lead->save();
        $order->customer()->associate($customer);
        $order->save();

        DB::commit();

        return response()->json();

    }

    function changeApplication(
        Request $request,
                $id
    )
    {
        try {
            $dateFrom = $request->input('date_from');
            $time_from = $request->input('time_from');
            $date = Carbon::parse("{$dateFrom} {$time_from}");

            $request->merge([
                'date_from' => (string)$date
            ]);
        } catch (\Exception $exception) {

        }
        $request->validate([
            'duration' => 'required|numeric|min:1|max:999',
            'date_from' => 'required|date',
            'delivery_cost' => 'required|numeric|min:0',
            'return_delivery' => 'required|numeric|min:0',
            'value_added' => 'nullable|numeric|min:0',
            'cost_per_unit' => 'required|numeric|min:0',
            'order_type' => 'required|in:hour,shift',
            'services' => 'nullable|array',
            'shift_duration' => 'nullable|integer|min:0|max:24',

        ]);
        $order = Order::contractorOrders()->findOrFail($id);
        $position = $order->components()->findOrFail($request->input('position_id'));

        $service = new OrderService();

        DB::beginTransaction();
        if ((int)$request->shift_duration && $position->worker instanceof Machinery) {
            $position->worker->update([
                'change_hour' => $request->input('shift_duration')
            ]);
        }
        $service->setOrder($order)->changeApplicationDuration
        (
            $request->input('position_id'),
            $date,
            $request->input('duration'),
            $request->input('order_type'),
            $request->input('cost_per_unit'),
            $request->input('delivery_cost'),
            $request->input('return_delivery'),
            $request->input('value_added'),
            $request->input('services'),
            $request->input('rent_parts')
        );
        DB::commit();
        OrderUpdatedEvent::dispatch($order);
        return response()->json();
    }

    function rejectApplication(
        Request $request,
                $id
    )
    {
        $request->validate([
            'position_ids' => 'required|array|min:1',
            'type' => 'required|string|in:' . LeadRejectReason::implodeInString(),
            'remove' => 'nullable|boolean',
        ]);
        $order = Order::contractorOrders()->findOrFail($id);

        $service = new OrderService();

        DB::beginTransaction();
        foreach ($request->input('position_ids') as $positionId) {
            $service->setOrder($order)->rejectApplication($positionId, $request->input('type'), $request->boolean('remove'));

        }

        if ($order->isAvitoOrder()) {
            $cancelReason = 'Сделка переведена в отказ';
            $status = AvitoOrder::STATUS_CANCELED;
            $autoSearch = config('avito.auto_search');
            if ($autoSearch) {
                $status = AvitoOrder::STATUS_CREATED;
                $cancelReason = '';
            }
            OrderChangedEvent::dispatch($order, $status, $cancelReason);
        }

        DB::commit();
        OrderUpdatedEvent::dispatch($order);
        return response()->json();
    }

    function saveActualApplication(
        Request $request,
                $id
    )
    {

        $request->validate([
            'order_type' => 'required|in:hour,shift',
            'amount' => 'nullable|numeric|min:0',
            'order_duration' => 'required|numeric|min:0',
            'cost_per_unit' => 'required|numeric|min:0',
            'value_added' => 'required|numeric|min:0',
            'delivery_cost' => 'required|numeric|min:0',
            'return_delivery' => 'required|numeric|min:0',
            'hours' => 'nullable|array',
            'hours.*.date' => 'required|date',
            'hours.*.time_from' => 'required',
            'hours.*.hours' => 'required|numeric|min:0',
        ]);
        $order = Order::contractorOrders()->findOrFail($id);

        $service = new OrderService();

        DB::beginTransaction();

        $service->setOrder($order)->setActualApplicationData($request->input('application_id'), $request->all());
        DB::commit();
    }

    function deleteActualApplication(
        Request $request,
                $id
    )
    {
        $order = Order::contractorOrders()->findOrFail($id);
        DB::beginTransaction();

        $application = $order->components()->findOrFail($request->input('application_id'));
        $application->actual()->delete();
        (new OrderService())->restoreCalendar($application);
        DB::commit();
    }


    function returnParts(
        Request $request,
                $id
    )
    {
        $request->validate([
            'items' => 'required|array',
            'items.*.id' => 'required',
            'items.*.amount' => 'required|min:0|numeric',
        ]);

        /** @var Order $order */
        $order = Order::forBranch()->findOrFail($id);
        if ($order->return_parts) {
            return response()->json([
                'errors' => 'Запчасти уже возвращены'
            ], 400);
        }
        $items = collect($request->input('items'));

        DB::transaction(function () use (
            $order,
            $items
        ) {

            foreach ($items as &$item) {

                // foreach ($order->components as $component) {
                $part = Item::query()->whereHasMorph('owner', [Posting::class])->forBranch()
                    //->where('stock_id', $item['stock_id'])
                    ->where('part_id', $item['id'])
                    ->first();
                $newPosting = new Item($part->toArray());
                $newPosting->amount = $item['amount'];

                $newPosting->owner()->associate($part->owner);
                $newPosting->oldOwner()->associate($order);
                $newPosting->save();
                // }
            }

            $order->update([
                'return_parts' => true
            ]);
        });

        /*   foreach ($items as $item) {

               $part = $position->parts->where('id', $item['id'])->first();
               if ($item['amount'] > $part->amount) {
                   $item['amount'] = $part->amount;
               }
               if ($part) {
                   $part->decrement('amount', $item['amount']);
               }
           }*/
    }

    function addPosition(
        Request $request,
                $id
    )
    {
        /** @var Order $order */
        $order = Order::contractorOrders()->findOrFail($id);
        if (!$request->filled('type') || $request->input('type') !== 'warehouse_set') {
            $request->validate([
                'vehicle.cashless_type' => 'nullable',
                'vehicle.comment' => 'nullable|string|max:255',
                'vehicle.company_worker_id' => 'nullable|exists:company_workers,id',
                'vehicle.cost_per_unit' => 'required|numeric|min:0',
                'vehicle.delivery_cost' => 'required|numeric|min:0',
                'vehicle.delivery_type' => 'required|string',
                'vehicle.distance' => 'required|numeric|min:0',
                'vehicle.order_type' => 'required|in:warm,cold',
                'vehicle.parts' => 'nullable|array',
                'vehicle.return_delivery' => 'required|numeric|min:0',
                'vehicle.services' => 'nullable|array',
                'vehicle.value_added' => 'nullable|numeric|min:0',

                'order_type.order_type' => 'required|in:shift,hour',
                'order_type.order_duration' => 'required|numeric|min:1',
                'order_type.start_date' => 'required|date',
                'order_type.start_time' => 'required|date_format:H:i',
            ]);
        }

        if (Cache::lock("update_order_{$order->id}", 10)->get()) {
            $orderService = new OrderServiceContractor($order, [], $request, $this->companyBranch);

            DB::transaction(function () use (
                $request,
                $order,
                $orderService
            ) {

                $item = $request->input('vehicle');
                $orderParams = $request->input('order_type');
                $type = $request->input('type');

                if (!$request->boolean('vehicle.split_by_month')) {
                    $orderService->addPositionItem($item, $orderParams, $type);
                } else {
                    $defaultDuration = $orderParams['order_duration'];
                    $dateFrom = Carbon::parse($orderParams['start_date']);
                    $dateTo = getDateTo($dateFrom, TimeCalculation::TIME_TYPE_SHIFT, $defaultDuration);
                    $month = [];
                    while ($dateFrom->format('Y-m') !== $dateTo->format('Y-m')) {
                        $duration = $dateFrom->copy()->endOfMonth()
                            ->diffInDays($dateFrom->copy()->endOfDay());
                        if (count($month) !== 0) {
                            $dateFrom->startOfDay();
                        }
                        $duration += 1;
                        $defaultDuration -= $duration;
                        $month[] = [
                            'date_from' => $dateFrom->copy(),
                            'duration' => $duration
                        ];
                        $dateFrom->addMonth()->startOfMonth();
                    }
                    $month[] = [
                        'date_from' => $dateFrom->copy(),
                        'duration' => $defaultDuration
                    ];

                    foreach ($month as $data) {
                        $orderService->addPositionItem($item, [
                            ...$orderParams,
                            'start_date' => $data['date_from']->format('Y-m-d'),
                            'start_time' => $data['date_from']->format('H:i'),
                            'order_duration' => $data['duration'],
                        ], $type);
                    }
                }
            });

            foreach ($orderService->oldChangeHours as $vId => $hour) {
                Machinery::query()->where('id', $vId)->update([
                    'change_hour' => $hour
                ]);
            }
            OrderUpdatedEvent::dispatch($order);
            Cache::lock("update_order_{$order->id}")->release();
        }
    }

    function upgradeSets(
        Request $request,
                $id)
    {
        $request->validate([
            'sets' => 'required|array',
            'prolongation' => 'nullable|integer|min:0',
        ]);

        /** @var Order $order */
        $order = Order::contractorOrders()->whereHas('machinerySets')->findOrFail($id);
        $service = new OrderService();
        $service->setOrder($order);
        DB::beginTransaction();

        foreach ($request->sets as $set) {
            /** @var MachinerySetsOrder $currentSet */
            $currentSet = $order->machinerySets()->findOrFail($set['id']);
            $prices = (array)$currentSet->prices;

            $sum = numberToPenny($set['sum']);
            $deliveryCost = numberToPenny($set['delivery_cost']);
            $returnDelivery = numberToPenny($set['return_delivery']);
            $deliverySum = $deliveryCost + $returnDelivery;
            $totalSum = $sum + $deliverySum;

            $prices['sum'] = $totalSum;
            $prices['delivery_cost'] = $deliveryCost;
            $prices['return_delivery'] = $returnDelivery;

            $currentSet->update([
                'prices' => $prices
            ]);

            $currentDuration = $currentSet->orderComponents->first()->order_duration;
            if ($request->input('prolongation')) {
                $currentDuration += $request->input('prolongation');
            }
            foreach ($currentSet->orderComponents as $component) {
                $component->update([
                    'delivery_cost' => $prices['delivery_cost'] / $currentSet->orderComponents->count(),
                    'return_delivery' => $prices['return_delivery'] / $currentSet->orderComponents->count(),
                    'cost_per_unit' => $sum / $currentSet->orderComponents->count() / $currentDuration,
                    'amount' => 0
                ]);
                if ($request->input('prolongation')) {
                    $service->prolongation($component->id, $request->input('prolongation'),
                        $sum / $currentSet->orderComponents->count() / $currentDuration);
                }

            }


        }

        DB::commit();
    }

    function updatePosition(Request $request, $id)
    {
        /** @var Order $order */
        $order = Order::contractorOrders()->findOrFail($id);
        $order->components()->where('id', $request->input('position_id'))
            ->update($request->only('description'));
    }

    function updateAvitoAdSum(Request $request, $id)
    {
        $order = Order::contractorOrders()->findOrFail($id);
        $position = $order->components()->where('id', $request->input('position_id'));
        $position->update([
            'avito_ad_sum' => $request->input('avito_ad_sum'),
        ]);
    }

    /**
     * @param Request $request
     * @param $id
     * @return void
     */
    function updateAvitoDotationSum(Request $request, $id): void
    {
        /** @var Order $order */
        $order = Order::contractorOrders()->findOrFail($id);
        $position = $order->components()->where('id', $request->input('position_id'));
        $position->update([
            'avito_dotation_sum' => $request->input('avito_dotation_sum')
        ]);
    }
}
