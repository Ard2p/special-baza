<?php

namespace Modules\ContractorOffice\Http\Controllers;

use App\Machinery;
use App\Machines\FreeDay;
use App\Machines\Type;
use App\Service\RequestBranch;
use App\User\EntityRequisite;
use App\User\IndividualRequisite;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Routing\Controller;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Modules\AdminOffice\Entities\Filter;
use Modules\CompanyOffice\Entities\Budget;
use Modules\CompanyOffice\Entities\CashRegister;
use Modules\CompanyOffice\Services\CompanyRoles;
use Modules\CompanyOffice\Services\ContactsService;
use Modules\ContractorOffice\Entities\TelematicData;
use Modules\ContractorOffice\Http\Requests\PersonRequest;
use Modules\ContractorOffice\Services\Tariffs\TimeCalculation;
use Modules\CorpCustomer\Entities\InternationalLegalDetails;
use Modules\Dispatcher\Entities\Customer;
use Modules\Dispatcher\Entities\Directories\Contractor;
use Modules\Dispatcher\Entities\DispatcherInvoice;
use Modules\Dispatcher\Entities\Lead;
use Modules\Dispatcher\Entities\LeadPosition;
use Modules\Dispatcher\Entities\PreLead;
use Modules\Integrations\Entities\Telpehony\TelephonyCallHistory;
use Modules\Orders\Entities\Order;
use Modules\Orders\Entities\OrderComponent;
use Modules\Orders\Entities\Payments\InvoicePay;
use Modules\Orders\Entities\Service\ServiceCenter;
use Modules\PartsWarehouse\Entities\Posting;
use Modules\PartsWarehouse\Entities\Shop\Parts\PartsSale;
use Modules\PartsWarehouse\Entities\Stock\Item;
use Rap2hpoutre\FastExcel\FastExcel;
use function Clue\StreamFilter\fun;

class ContractorOfficeController extends Controller
{

    private $companyBranch;

    public function __construct(Request $request, RequestBranch $companyBranch)
    {
        $this->companyBranch = $companyBranch->companyBranch;
        $block = $this->companyBranch->getBlockName(CompanyRoles::BRANCH_DASHBOARD);
        $this->middleware("accessCheck:{$block},".CompanyRoles::ACTION_SHOW)->only([
            'mainCategories',
            'getStats',
            'getSubContractorsStats',
            'getInvoices',
            'getTelematicStats',
            'getOrderStats',
            'getCalendarStats',
            'getAttentionData',
            'getUtilizationData',
            'getRejectStats',
            'findCustomers',
            'updatePerson',
        ]);
        // $this->middleware("accessCheck:{$block}," . CompanyRoles::ACTION_CREATE)->only(['store', 'update']);
        //  $this->middleware("accessCheck:{$block}," . CompanyRoles::ACTION_DELETE)->only(['destroy', 'closeLead']);

    }

    function updatePerson(PersonRequest $request, $id)
    {
        $service = new ContactsService($this->companyBranch);
        $service->updateContact($id, $request->all());
    }

    function findPersons(Request $request) {
        /** @var Buil $persons */
        $persons = IndividualRequisite::query()
            ->with('phones', 'emails', 'principals')
            ->where('type', IndividualRequisite::TYPE_PERSON)
            ->forBranch();
        if ($request->filled('id')) {

            $persons->where('id', $request->input('id'));

        } else {
            $persons->where(function (Builder $persons) use (
                $request
            ) {
                $phone = trimPhone($request->input('search_word'));
                $persons
                    ->where(DB::raw("CONCAT(firstname, ' ', middlename, ' ', surname, ' ', firstname)"), 'like', "%{$request->input('search_word')}%")
                    ->when($phone, fn(Builder $b) => $b->orWhereHas('phones', fn(Builder $builder) => $builder->where($builder->qualifyColumn('phone'), 'like', "%{$phone}%")) );
            });

        }

        return $persons->orderBy('firstname')->limit(10)->get();
    }
    function findCustomers(Request $request)
    {
        $customers = Customer::query()
            ->with('serviceContracts.requisite', 'tags', 'contracts.requisite', 'manager_notes', 'tasks', 'domain', 'contacts.principals', 'contacts.phones', 'contacts.emails', 'entity_requisites', 'individual_requisites', 'scoring')
            ->with(['orders' => function(MorphMany $builder) {
                $builder->with('vehicles')->where('status', Order::STATUS_ACCEPT);
            }])
            ->withCount( ['orders','leads' => fn($q) => $q->where('is_fast_order', 0)])
            ->withSum('orders', 'amount')
            ->forBranch();

        if ($request->filled('has_tasks')) {
            $customers->has('tasks');
        }

        if ($request->filled('has_tasks_order_id')) {
            $customers->whereHasMorph('orders', Order::class, function ($q) use ($request) {
                $q->has('tasks')->where('id', $request->has_tasks_order_id);
            });
        }

        if ($request->filled('id')) {

            $customers->where('id', $request->input('id'));

        } else {
            $customers->where(function (Builder $customers) use (
                $request
            ) {
                $customers->where('company_name', 'like', "%{$request->input('search_word')}%")
                    ->orWhere('phone', 'like', "%{$request->input('search_word')}%")
                    ->orWhereRelation('entity_requisites', 'inn', 'like', "%{$request->input('search_word')}%")
                    ->orWhereRelation('individual_requisites', 'inn', 'like', "%{$request->input('search_word')}%");
            });

        }
        $customers = $customers->orderBy('company_name')->limit(10)->get();
        return $customers->map(function (Customer $customer) use ($request) {
            if ($request->filled('has_tasks_order_id')) {
                $latestTask = $customer->tasks()->whereHasMorph('orders', Order::class, function ($q) use ($request) {
                    $q->has('tasks')->where('id', $request->has_tasks_order_id);
                })->latest()->first();
            } else {
                $latestTask = $customer->tasks->last();
            }
            $customer->last_addresses = DB::table('orders')->where([
                'customer_id' => $customer->id,
                'customer_type' => Customer::class
            ])->limit(5)
                ->orderBy('id', 'desc')
                ->get(['id', 'address', 'coordinates', 'region_id', 'city_id'])->map(fn($order) => [
                    'id' => $order->id,
                    'address' => $order->address,
                    'coordinates' => getDbCoordinates($order->coordinates),
                    'region_id' => $order->region_id,
                    'city_id' => $order->city_id,
                ]);
            $customer->setAppends(['balance_info']);
            $customer->company_name_original .= $customer->company_name;
            $customer->latest_task = ($latestTask) ? $latestTask->date_from : null;
            $customer->notes = $customer->manager_notes;
            $customer->vehicles = $customer->orders->flatMap(fn($order) => $order->vehicles);
            return $customer;
        });
    }

    function findContractors(Request $request)
    {

        $customers = Contractor::query()->forBranch();

        if ($request->filled('id')) {

            $customers->where('id', $request->input('id'));

        } else {
            $customers->where('company_name', 'like', "%{$request->input('search_word')}%");
        }

        return $customers->get();
    }

    function getStats(Request $request)
    {
        $request->merge([
            'machinery_base_id' => 'all'
        ]);
        $request->validate([
            'date_from' => 'required|date|before_or_equal:date_to',
            'date_to' => 'required|date|before_or_equal:'.now()->endOfMonth()->format('Y-m-d'),
        ]);
        $startPeriod = $request->filled('date_from')
            ? Carbon::parse($request->input('date_from'))
            : now()->startOfMonth();

        $endPeriod =
            $request->filled('date_to')
                ? (Carbon::parse($request->input('date_to')))
                : now()->endOfDay();
        if($endPeriod->gt(now())) {
            $endPeriod = now()->endOfDay();
            $request->merge([
                'date_to' => $endPeriod->toDateTimeString(),

            ]);
        }
        $contractStart = $startPeriod->clone();
        $contractEnd = $endPeriod->clone();

        $count = Machinery::forBranch()
            ->whereHas('freeDays', function (Builder $q) {
                $q->whereHas('technicalWork')->forPeriod(now(), now());
            })
            ->forDomain()->count();

        $util_per_day = [];
        $range = FreeDay::generateDateRange(clone $startPeriod, clone $endPeriod);
        foreach ($range as $date) {

            $util_per_day[] = $this->getUtilization($date, $date, true)['percent'];
        }

        $utilization_curr_period = $this->getUtilization($startPeriod, $endPeriod);
        $utilization_prev_period =
            $this->getUtilization($startPeriod->clone()->subDays($endPeriod->diffInDays($startPeriod) + 1), $startPeriod->clone());
        $utilization_prev_year = $this->getUtilization($startPeriod->subYear()->clone(), $endPeriod->subYear()->clone());

        $overdueOrders = Order::query()->forBranch()
            ->whereHas('components', function ($q) {
                $q->where('date_to', '<', now())
                     ->where('status', Order::STATUS_ACCEPT)
                         ->where('worker_type', Machinery::class);
            })
            //->withCount([
            //    'vehicle_timestamps as doneStatusCount' => function ($q) {
            //        $q->where('type', 'done');
            //    }
            //])
            //->withCount('vehicle_timestamps')
           // ->where('status', Order::STATUS_ACCEPT)
            //->havingRaw('doneStatusCount < vehicle_timestamps_count')
            ->count();

        $startTodayOrders = Order::query()
            ->forBranch()
            ->whereHas('components', function (Builder $q) {
                $q->havingRaw('MIN(`date_from`) between ? and ?', [now()->startOfDay(), now()->endOfDay()]);
            })
            ->where('tmp_status', '!=', Order::STATUS_REJECT)
            ->count();

        $startRomorrowOrders = Order::query()->forBranch()->startTomorrow()
            ->withCount([
                'vehicle_timestamps as doneStatusCount' => function ($q) {
                    $q->where('type', 'done');
                }
            ])
            ->withCount('vehicle_timestamps')
            ->where('status', Order::STATUS_ACCEPT)
            ->havingRaw('doneStatusCount < vehicle_timestamps_count')
            ->get()
            ->count();

        $overduePaysOrders = Order::query()
            ->forBranch()
            ->withAggregate('components as value_added', 'value_added', 'sum')
            ->where(function ($orders) {
                     $orders->whereHas('invoices', function (Builder $q) {
                         $q->havingRaw('SUM(paid_sum) < `orders`.`amount`');
                     })->orWhereDoesntHave('invoices');
                 })->whereNotIn('status', [Order::STATUS_REJECT, Order::STATUS_CLOSE])
            ->with('invoices')
            ->get()
            ->sum(fn($order) => $order->amount - $order->invoices->sum('paid_sum'));

        $cashBox = $this->getCashBox($request);

        $rentRevenue = $cashBox['cashRentPaysInPeriod'] + $cashBox['cashlessRentPaysInPeriod'];
        $rentRevenueVat = $cashBox['cashRentPaysInPeriodVat'] + $cashBox['cashlessRentPaysInPeriodVat'];

        $serviceRevenue = $cashBox['cashlessPaysServiceInPeriod'] + $cashBox['cashPaysServiceInPeriod'];
        $serviceRevenueVat = $cashBox['cashlessPaysServiceInPeriodVat'] + $cashBox['cashPaysServiceInPeriodVat'];

        $cashflow = $rentRevenue + $serviceRevenue;
        $cashflowVat = $rentRevenueVat + $serviceRevenueVat;

        return response()->json([
                'util_per_day' => $util_per_day,
                'today_machinery_service' => $count,
                'in_work_leads' => 0,
                'contracts_count' => Customer\CustomerContract::query()->whereBetween('created_at', [$contractStart, $contractEnd])
                    ->whereHasMorph('customer', [Customer::class], function ($q) {
                        $q->forBranch();
                    })
                    ->count(),
                'orders' => [
                    'cur_period' => $utilization_curr_period['orders_count'],
                    'prev_period' => $utilization_prev_period['orders_count'],
                    'prev_year' => $utilization_prev_year['orders_count'],
                    'overdue_orders' => $overdueOrders,
                    'overdue_pays' => $overduePaysOrders,
                    'start_today' => $startTodayOrders,
                    'start_tomorrow' => $startRomorrowOrders,
                ],
                'revenue' => [
                    'value_added' => 0,//$utilization_curr_period['value_added'],
                    'cur_period' => $utilization_curr_period['amount'],
                    'prev_period' => $utilization_prev_period['amount'],
                    'prev_year' => $utilization_prev_year['amount'],
                    'rent' => $rentRevenue,
                    'rent_vat' => $rentRevenueVat,
                    'service' => $serviceRevenue,
                    'service_vat' => $serviceRevenueVat,
                    'cashflow' => $cashflow,
                    'cashflow_vat' => $cashflowVat,
                ],
                'utilization' => [
                    'cur_period' => $utilization_curr_period['percent'],
                    'prev_period' => $utilization_prev_period['percent'],
                    'prev_year' => $utilization_prev_year['percent'],
                ]
            ] + $this->companyBranch->getDashboardInfo());
    }


    function getReport(Request $request)
    {
        $request->validate([
            'date_from' => 'required|date|before_or_equal:date_to',
            'date_to' => 'required|date|before_or_equal:'.now()->addDay()->format('Y-m-d'),
        ]);
        $startPeriod = $request->filled('date_from')
            ? Carbon::parse($request->input('date_from'))->startOfMonth()
            : now()->startOfMonth();

        $endPeriod =
            $request->filled('date_to')
                ? Carbon::parse($request->input('date_to'))->endOfMonth()
                : now()->endOfMonth();

        $orders = Order::query()->forBranch()
            ->whereHas('components', function (Builder $q) use (
                $request,
                $startPeriod,
                $endPeriod
            ) {
                $q->where('status', '!=', Order::STATUS_REJECT)
                    ->whereHasMorph('worker', [Machinery::class], function (Builder $q) use (
                        $request
                    ) {
                        if (toBool($request->input('subContractor'))) {
                            $q->whereNotNull('sub_owner_id');
                        }

                        $filter = new Filter($q);
                        $filter->getEqual([
                            'category_id' => 'type',
                            'brand_id' => 'brand_id',
                            'model_id' => 'model_id',
                        ])->getLike(['name' => 'name']);
                    })->forPeriod($startPeriod, $endPeriod);
            });
        if ($request->input('manager_id')) {
            $orders->forManager($request->input('manager_id'));

        }
        /* $components = OrderComponent::query()
             ->where('status', '!=', Order::STATUS_REJECT)
             ->whereHasMorph('worker', [Machinery::class], function (Builder $q) use ($request) {
                 if (toBool($request->input('subContractor')))
                     $q->whereNotNull('sub_owner_id');

                 $filter = new Filter($q);
                 $filter->getEqual([
                     'category_id' => 'type',
                     'brand_id' => 'brand_id',
                     'model_id' => 'model_id',
                 ])->getLike(['name' => 'name']);
             })
             ->whereHas('order', function ($q) use ($request) {
                 if ($request->input('manager_id')) {
                     $q->forManager($request->input('manager_id'));

                 }
                 $q->forBranch();
             })->forPeriod($startPeriod, $endPeriod);
         if ($request->filled('excel')) {


             return $this->makeExcel($components);
         }*/
        $orders =
            $orders->paginate($request->perPage
                ?: 8);
        $orders->getCollection()->transform(function ($order) use (
            $startPeriod,
            $endPeriod
        ) {

            $order->components->map(function ($item) use (
                $startPeriod,
                $endPeriod
            ) {
                if ($item->order_type === TimeCalculation::TIME_TYPE_SHIFT) {
                    $count = $item->calendar()->whereBetween('startDate', [$startPeriod, $endPeriod])->count();
                    if ($item->worker->change_hour === 24 && $item->date_to->lte($endPeriod) && $count > 1) {
                        $count -= 1;
                    }
                } else {
                    $count = $item->order_duration;
                }


                $item->report_return_delivery = $item->return_delivery;
                $item->report_delivery_cost = $item->delivery_cost;
                $item->report_services = 0;
                $item->services_amount = $item->services_sum;
                if ($item->date_from->lt($startPeriod)) {
                    $item->report_delivery_cost = 0;
                } else {
                    $item->report_services += $item->services_sum;
                }


                if ($item->date_to->gt($endPeriod)) {
                    $item->report_return_delivery = 0;
                }

                $item->report_sum =
                    $item->cost_per_unit * $count + ($item->report_services + $item->report_delivery_cost + $item->report_return_delivery);
                $item->sum = $item->cost_per_unit * $count + $item->value_added;

                $item->report_duration = $count;


                $invQuery =
                    DispatcherInvoice::query()->whereHas('orderComponents', function (Builder $q) use (
                        $item
                    ) {
                        $q->where('order_workers.id', $item->id);
                    })->whereBetween('created_at', [$startPeriod, $endPeriod]);
                $item->invoice = [
                    'sum' => $invQuery->sum('sum'),
                    'paid_sum' => $invQuery->sum('paid_sum')
                ];
                if ($item->driver) {
                    $item->actual_duration = $item->reports->sum('total_hours');
                }

                $item->contract = $item->order->customer->contract;
                //$item->order = $item->order->only(['id', 'address', 'customer', 'manager']);

                $item->worker->contractor = $item->worker->subOwner;
                return $item;
            });
            $responseOrder = (object) $order->toArray();

            $responseOrder->report_return_delivery = $order->components->sum('report_return_delivery');
            $responseOrder->sum = $order->components->sum('sum');
            $responseOrder->total_sum = $order->components->sum('total_sum');
            $responseOrder->value_added = $order->components->sum('value_added');
            $responseOrder->report_delivery_cost = $order->components->sum('report_delivery_cost');
            $responseOrder->report_services = $order->components->sum('report_services');
            $responseOrder->services_amount = $order->components->sum('services_amount');
            $responseOrder->report_sum = $order->components->sum('report_sum');
            $responseOrder->report_duration = $order->components->sum('report_duration');
            $responseOrder->invoice = [
                'sum' => $order->components->sum('invoice.sum'),
                'paid_sum' => $order->components->sum('invoice.paid_sum'),
            ];
            $responseOrder->actual_duration = $order->components->sum('actual_duration');
            $responseOrder->contract = $order->customer->contract;
            $responseOrder->report_duration_shifts =
                $order->components->where('order_type', TimeCalculation::TIME_TYPE_SHIFT)->sum('report_duration');
            $responseOrder->report_duration_hours =
                $order->components->where('order_type', TimeCalculation::TIME_TYPE_HOUR)->sum('report_duration');
            $responseOrder->company = $order->customer;
            $responseOrder->currency = $order->currency;

            return $responseOrder;

        });
        return $orders;
    }

    function getInvoices(Request $request)
    {

        /** @var Builder $invoices */
        $invoices = DispatcherInvoice::query()->forBranch()->with('positions');
        $filter = new Filter($invoices);
        $filter->getDateBetween([
            'date_from' => 'created_at',
            'date_to' => 'created_at',
        ])->getLike([
            'number' => 'number'
        ]);

        if($request->filled('requisite')) {
            $reqData = explode('_', $request->input('requisite'));
            if ($req = $this->companyBranch->findRequisiteByType($reqData[1], $reqData[0])) {
                $invoices->whereHasMorph('owner', [Order::class, ServiceCenter::class], function ($q) use ($request, $req) {
                    $q->whereRelation('contract', 'requisite_id', $req->id);
                    $q->whereRelation('contract', 'requisite_type', get_class($req));
                });

            }
        }

        if($request->filled('customer_id')) {
            $invoices->whereHasMorph('owner', [Order::class, ServiceCenter::class], function ($q) use ($request) {
                $q->whereRelation('customer', 'id', $request->input('customer_id'));
            });
        }

        if($request->filled('internal_number')) {
            $invoices->whereHasMorph('owner', [Order::class, ServiceCenter::class], function ($q) use ($request) {
                $q->where('internal_number', $request->input('internal_number'));
            });
        }

        if($request->filled('contract_number')) {
            $invoices->whereHasMorph('owner', [Order::class, ServiceCenter::class], function ($q) use ($request) {
                $q->whereRelation('contract', 'current_number', $request->input('contract_number'));
            });
        }



        if ($request->filled('sortBy')) {
            $sort =
                toBool($request->input('sortDesc'))
                    ? 'desc'
                    : 'asc';
            switch ($request->input('sortBy')) {
                case 'created_at':
                    $invoices->orderBy('created_at', $sort);
                    break;
                case 'company_name':
                    $invoices->orderBy('requisite_id', $sort);
                    break;
            }
        } else {
            $invoices->orderBy('created_at', 'desc');
        }

        if ($request->filled('deposits')) {
            $invoices->whereHasMorph('owner', Lead::class)
                ->withSum(
                    'donorTransfers as transferSum', 'sum',
                )
                ->havingRaw('`paid_sum` > transferSum');
            return $invoices->get()->map(function ($item) {
                if ($item->owner instanceof Order) {
                    $item->order_id = $item->owner->id;
                    $item->internal_number = $item->owner->internal_number;
                }
                if ($item->owner instanceof Lead) {
                    $item->lead_id = $item->owner->id;
                    $item->internal_number = $item->owner->internal_number;
                }
                $item->append('customer');
                if ($item->customer) {
                    $item->contract = $item->customer->contract;
                }
                return $item;
            });
        }
        if ($request->filled('pay_type')) {

            switch ($request->input('pay_type')) {
                case 'paid':

                    /*    $orders->withCount(['invoices as total_paid'=> function (Builder $q) {
                            $q->selectRaw('SUM(paid_sum)');
                           //$q->whereRaw('SUM(`dispatcher_invoices`.`paid_sum`) >= `amount`');
                        }])->havingRaw('total_paid >= orders.amount');*/
                    $invoices->where('is_paid', 1);
                    break;
                case 'partial':

                    $invoices->whereRaw('`paid_sum` < `sum` AND `paid_sum` > 0');
                    //$q->whereRaw('SUM(`dispatcher_invoices`.`paid_sum`) >= `amount`');

                    break;
                case 'not_paid':
                    $invoices->has('pays', '=', 0);
                    break;
            }
        }

        $invQuery = $invoices;
        $invoices =
            $invoices->paginate($request->perPage ?: 10);

        $invoices->getCollection()->transform(function (DispatcherInvoice $item) {
            if ($item->owner instanceof Order) {
                $item->order_id = $item->owner->id;
                $item->internal_number = $item->owner->internal_number;
            }
            if ($item->owner instanceof Lead) {
                $item->lead_id = $item->owner->id;
                $item->internal_number = $item->owner->internal_number;
            }
            $item->append('customer');

            if ($item->customer) {
                if ($item->owner instanceof Lead) {
                    $item->contract = $item->owner?->customerContract;
                } else {
                    $item->contract = $item->owner?->contract ?? $item->customer->contract;
                }
            }

            if ($item->driver) {
                $item->actual_duration = $item->reports->sum('total_hours');
            }

            return $item;
        });

        $allIds = $invQuery->pluck('id');
        $additional = [
            'cash' => InvoicePay::query()
                ->whereType('cash')
                ->whereIn('invoice_id', $allIds)
                ->sum('sum'),// $invQuery->sum('sum'),
            'cashless' => InvoicePay::query()
                ->whereType('cashless')
                ->whereIn('invoice_id', $allIds)
                ->sum('sum'),
            'sum' => $invQuery->sum('sum'),
            'paid_sum' => $invQuery->sum('paid_sum'),
        ];
        
        return JsonResource::collection($invoices)->additional($additional);
    }

    function getFullReport(Request $request)
    {

    }

    private function makeExcel($components)
    {
        $file = "report_".uniqid().".xlsx";
        $path = config('app.upload_tmp_dir')."/{$file}";
        (new FastExcel($components))->export(public_path($path), function ($component) {

            $invQuery =
                DispatcherInvoice::query()->whereHas('orderComponents', function (Builder $q) use (
                    $component
                ) {
                    $q->where('order_workers.id', $component->id);
                });

            $paidSum = $invQuery->sum('paid_sum');

            return [
                trans('transbaza_order.status') => Order::statuses()[$component->status],
                'Номер УДП' => $component->udp_number,
                'Дата УДП' => $component->udp_date
                    ? $component->udp_date->format('d.m.Y')
                    : '',
                'Номер сделки' => $component->order->internal_number,
                trans('contractors/edit.application_number') => $component->application_id,
                trans('mailing/search_company.contract_number') => $component->order->customer->contract
                    ? $component->order->customer->contract->current_number
                    : '',
                trans('transbaza_proposal.customer') => $component->order->customer->company_name,
                trans('transbaza_proposal_search.address') => $component->order->address,
                trans('transbaza_proposal_search.start_date') => $component->date_from->format('d.m.Y'),
                trans('transbaza_proposal.amount_sh') => $component->order_duration,
                trans('transbaza_machine_index.title') => $component->worker->name,
                trans('contractors/edit.driver') => $component->driver
                    ? $component->driver->name
                    : '',
                trans('contractors/edit.order_type_hour_shift') => ($component->order_type
                    ?
                    trans('transbaza_register_order.shifts')
                    :
                    trans('contractors/edit.hour')),
                trans('contractors/edit.cost_hour_shift') => ($component->cost_per_unit + $component->value_added) / 100,
                'Аренда' => ($component->cost_per_unit + $component->value_added) * $component->order_duration / 100,
                trans('commercial_offer/commercial_offer.delivery_cost') => ($component->delivery_cost) / 100,
                trans('contractors/edit.сost_return_delivery_number') => ($component->return_delivery) / 100,
                trans('tb_messages.in_total') => ($component->total_sum) / 100,


                trans('transbaza_order.billed_to') => $invQuery->sum('sum') / 100,
                trans('transbaza_order.paid') => $paidSum / 100,

                trans('transbaza_order.remains_paid_out') => ($component->total_sum - $paidSum) / 100,

                trans('transbaza_contractor.contractor') => ($component->worker->subOwner
                    ? $component->worker->subOwner->company_name
                    : ''),


                trans('transbaza_contractor.contractor').' '.trans('contractors/edit.cost_hour_shift') => ($component->cost_per_unit) / 100,
                trans('transbaza_contractor.contractor').' '.trans('tb_messages.cost') => ($component->cost_per_unit * $component->order_duration) / 100,
                'Маржа' => ($component->value_added) / 100,
                'Маржа за аренду' => ($component->value_added * $component->order_duration) / 100,


            ];
        });

        Storage::disk()->put($path, Storage::disk('public_disk')->get($path));

        Storage::disk('public_disk')->delete($path);


        return Storage::disk()->url($path);
    }

    function getUtilization(
        Carbon $dateFrom,
        Carbon $dateTo,
               $perDay = false
    ) {
        $dateFrom = $dateFrom->copy();
        $dateTo = $dateTo->copy();
        $utilization = collect();
        $components = OrderComponent::query()
            ->where('status', '!=', Order::STATUS_REJECT);
        $components->whereHas('order', function ($q) {
            $q->forBranch();
        });
        $days = $dateTo->diffInDays($dateFrom) + 1;

        $components->forPeriod($dateFrom, $dateTo, $perDay);
        $components = $components->with('worker')->get();

        foreach ($components as $component) {

            $dates = FreeDay::generateDateRange($component->date_from, $component->date_to, true);

            /** @var Carbon $date */
            foreach ($dates as $date) {

                if (!$date->betweenIncluded($dateFrom, $dateTo)) {
                    continue;
                }
                $count = count($dates);
                if ($component->worker->change_hour === 24 && $count > 1) {
                    $count -= 1;
                }
                $utilization->push([
                    'id' => $component->worker_id,
                    'date' => $date,
                    'order_id' => $component->order_id,
                    'manager' => '',//($component->order->manager ? $component->order->manager->contact_person : ''),
                    'amount' => $component->amount / $count,
                    'value_added' => $component->value_added / $count,
                ]);
            }
        }
        $total =
            Machinery::query()->forDomain()->where(function (Builder $q) use (
                $dateFrom,
                $dateTo
            ) {
                $q->whereBetween('created_at', [$dateFrom, $dateTo])
                    ->orWhere(function ($q) use (
                        $dateFrom,
                        $dateTo
                    ) {
                        $q->where('created_at', '<=', $dateFrom)
                            ->where('created_at', '<=', $dateTo);
                    });
            })->forBranch()->count();

        $total = $days * $total;
        return [
            'amount' => $utilization->sum('amount'),
            'value_added' => $utilization->sum('value_added'),
            'percent' => ($total
                ? round(100 * $utilization->count() / $total, 2)
                : 0),
            'orders_count' => $utilization->unique('order_id')->count(),
        ];
    }

    public function getUtilizationV2(Request $request)
    {
        $dateFrom = $request->date('date_from')->startOfMonth();
        $dateTo =  $request->date('date_to')->endOfMonth();
        $months = CarbonPeriod::create($dateFrom, '1 month', $dateTo);

        if ($request->filled('utilization')) {
            [$fromPercent, $toPercent] = $request->input('utilization');
        } else {
            $fromPercent = 0;
            $toPercent = 100;
        }
        /** @var LengthAwarePaginator $machineriesData */
        $machineriesData = Machinery::query()
            ->when($fromPercent > 0 || $toPercent <= 100, function (Builder $builder) use ($months, $fromPercent, $toPercent, $request) {

                $havingRawSql = '';
                foreach ($months as $k => $month) {
                    $format = $month->format('Ym');
                    $days = ($month->copy()->endOfMonth()->diffInDays($month->copy()->startOfMonth()) + 1);
                    $builder->withCount([
                        "order_position as {$format}percent" => function ($q) use (
                            $request,
                            $days,
                            $fromPercent,
                            $toPercent,
                            $month
                        ) {

                            $q->forPeriod($month->copy()->startOfMonth(), $month->endOfMonth());

                            if ($request->filled('manager_id')) {
                                $q->whereHas('order', function ($q) use (
                                    $request
                                ) {
                                    $q->forManager($request->input('manager_id'));
                                });
                            }
                            $q->select(DB::raw("(case when SUM(order_workers.order_duration) > {$days} then {$days} else SUM(order_workers.order_duration) end)  * 100 / {$days}"));
                            // ->where('order_type', TimeCalculation::TIME_TYPE_SHIFT)

                        }
                    ]);
                    $havingRawSql .= "({$format}percent > {$fromPercent} and {$format}percent <= {$toPercent})";
                    if (count($months) !== ($k + 1)) {
                        $havingRawSql .= ' or ';
                    }
                }
                if($fromPercent > 0) {
                    $builder->havingRaw($havingRawSql);
                }

            })
            ->forBranch();
           // ->whereIn('id', $request->input('vehicles'))
        (new Filter($machineriesData))->getLike([
            'name' => 'name'
        ])->getEqual([
            'category_id' => 'type',
            'brand_id' => 'brand_id',
            'model_id' => 'model_id',
            'base_id' => 'default_base_id',
        ]);
        switch ($request->input('type')) {
            case 'company':
                $machineriesData->whereNull('sub_owner_id');
                break;
            case 'sub':
                $machineriesData->whereNotNull('sub_owner_id');

        }
        if ($request->filled('sortBy')) {
            $sort = $request->input('sortBy');
            $type =
                toBool($request->input('asc'))
                    ? 'asc'
                    : 'desc';

            switch ($sort) {
                case 'utilization_percent':
                    $machineriesData->orderBy($dateFrom->format('Ym').'percent', $type);
                    break;
                case 'name':
                    $machineriesData->orderBy('name', $type);
                    break;
                case 'board_number':
                    $machineriesData->orderBy('board_number', $type);
                    break;
            }
        }
        $machineriesData = $machineriesData->paginate($request->input('per_page'));
        $machineries = $machineriesData->getCollection();

        $orders = OrderComponent::query()
            ->forPeriod($dateFrom, $dateTo)
            ->accepted()
            ->where('worker_type', Machinery::class)
            ->whereIn('worker_id', $machineries->pluck('id'))
            ->when($request->input('manager_id'), fn(Builder $builder) => $builder->whereHas('order', fn(Builder $orderBuilder) => $orderBuilder->forManager($request->input('manager_id'))))
            ->get();


        $services = ServiceCenter::query()
            ->with(['works', 'parts'])
            ->withPaidInvoiceSum()
            ->forBranch()
            ->where('status_tmp', ServiceCenter::STATUS_ISSUED)
            ->whereBetween('date_to', [$dateFrom, $dateTo])
            ->whereIn('machinery_id', $machineries->pluck('id'))
            ->get();
        $result = [];

        foreach ($machineries as $machinery) {
            $result[$machinery->id] = [];
            foreach ($months as $k => $month) {
                $result[$machinery->id][$month->format('m-Y')] = [];
                $days = CarbonPeriod::create($month->copy()->setTimezone($this->companyBranch->timezone)->startOfMonth(), '1 day', $month->copy()->setTimezone($this->companyBranch->timezone)->endOfMonth());

                $prevOrder = null;
                foreach ($days as $day) {
                    $component = $orders->where('worker_id', $machinery->id)
                        ->filter(fn(OrderComponent $orderComponent) =>
                        $day->between(
                            $orderComponent->date_from->copy()->startOfDay(),
                            getDateTo($orderComponent->date_from, $orderComponent->order_type, $orderComponent->order_duration)
                        ))
                        ->first();

                    $service = $services->where('machinery_id', $machinery->id)
                        ->filter(fn(ServiceCenter $orderComponent) => $day->between($orderComponent->date_from->startOfDay(), $orderComponent->date_to->endOfDay()))
                        ->first();
                    $result[$machinery->id][$month->format('m-Y')][$day->format('d')] = [
                        'rent' => $component?->cost_per_unit,
                        'service' => $service?->prepared_sum,
                        'service_id' => $service?->id,
                        'service_internal_number' => $service?->internal_number,
                        'service_paid' => (int) $service?->paid_sum,
                        'internal_number' => $component?->order_internal_number,
                        'order_id' => $component?->order_id,
                        'cost_per_unit' => ($component->cost_per_unit ?? 0) / 100,
                        'customer_name' => $component?->order->customer->company_name,
                    ];
                    $prevOrder = $component;
                }
            }

        }
        return [
            'vehicles' => $machineriesData,
            'data' => $result
        ];
    }


    function mainCategories(Request $request)
    {
        return Type::setLocaleNames(Type::query()->whereHas('machines', function ($q) {
            $q->forBranch();
        })->get());
    }

    function getTelematicStats(Request $request)
    {
        $startPeriod = $request->filled('start_date')
            ? Carbon::createFromFormat('Y-m-d', $request->input('start_date'))
            : now()->subDays(30);

        if ($startPeriod->gt(now())) {
            $startPeriod = now()->subDay();
        }

        $vehicles = TelematicData::currentUser()->where('period_from', '>=', $startPeriod)->get();

        $vehicles->map(function ($vehicle) {
            $seconds = Carbon::createFromFormat('H:m:s', $vehicle->working_hours)->secondsSinceMidnight();
            $vehicle->total_hours = $seconds / 60;
            return $vehicle;
        });
        return response()->json([
            'average_speed' => round($vehicles->avg('average_speed')),
            'total_hours' => round($vehicles->sum('total_hours')),
            'average_fuel' => round($vehicles->avg('average_fuel')),
            'mileage' => round($vehicles->avg('mileage') / 1000),
            'fuel_consumption_abs' => round((int) $vehicles->sum('fuel_consumption_fls')),
        ]);
    }


    function getOrderStats()
    {
        $orders =
            Order::forBranch()->whereIn('status',
                [Order::STATUS_ACCEPT, Order::STATUS_HOLD, Order::STATUS_OPEN])->get();

        $orders->each->setAppends(['types_list_text', 'sum_format']);

        $in_progress = Order::forBranch()->whereStatus(Order::STATUS_ACCEPT)->count();

        return response()->json([
            'orders' => $orders->toArray(),
            'in_progress' => $in_progress,
            'wait_confirm' => $in_progress,
            //Order::currentUser()->whereStatus(Order::STATUS_ACCEPT)->whereDate('date_to', '<=', now())->count(),
        ]);
    }


    /**
     * Массив для диаграмы Ганта на фронте в дашборде
     * @return array
     */
    function getCalendarStats(Request $request)
    {

        $collection = collect();
        $v_collection = collect();


        $vehicles = Machinery::query()->with('base');

        if ($request->filled('category_id')) {
            $vehicles->where('type', $request->input('category_id'));
        }

        if ($request->filled('base_id')) {
            $vehicles->where('base_id', $request->input('base_id'));
        }
        if (is_array($request->input('categories'))) {
            $vehicles->whereIn('type', Arr::flatten($request->input('categories')));
        }

        $vehicles = $vehicles->forBranch()->get();
        $calendar = collect();
        foreach ($vehicles as $vehicle) {

            $v_collection->push([
                'id' => $vehicle->id,
                'title' => $vehicle->name.($vehicle->base
                        ? " ({$vehicle->base->name})"
                        : ''),
                'board_number' => $vehicle->board_number,
                'category_id' => $vehicle->type,
                'model_id' => $vehicle->model_id,
                'model_name' => $vehicle->model
                    ? $vehicle->model->name
                    : '',
                'type' => $vehicle->machine_type,
                'worker_id' => trans('transbaza_machine_index.title'),
                /*'children' => $vehicle->orders->map(function ($item) use ($vehicle) {

                    $new = [
                        'id' => "{$vehicle->id}_{$item->id}",
                        'order_id' => $item->id,
                        'title' => trans('transbaza_order.order') . " #{$item->id}"
                    ];
                    return $new;
                })*/

            ]);
            $calendar = $calendar->merge($vehicle->freeDays);
        }

//        foreach ($calendar->sortBy('machine_id') as $period) {
//
//            $collection->push([
//                'order_id' => $period->order_id,
//                'resourceId' => "{$period->machine_id}_{$period->order_id}",
//                'title' => $period->title,
//                'start' => $period->startDate->format('Y-m-d H:i'),
//                'end' => $period->endDate->format('Y-m-d H:i'),
//                'color' => $period->color,
//
//            ]);
//            $collection->push([
//                'order_id' => $period->order_id,
//                'resourceId' => $period->machine_id,
//                'title' => $period->title,
//                'start' => $period->startDate->format('Y-m-d H:i'),
//                'end' => $period->endDate->format('Y-m-d H:i'),
//                'color' => $period->color,
//                'manager' => $period->manager ? $period->manager->id_with_email : '',
//            ]);
//
//        }
//
//
//        $workers = OrderComponent::query()
//            ->where('worker_type', Contractor::class)
//            ->whereHas('order', function ($q) {
//                $q->contractorOrders();
//            })
//            ->get();
//
//        if ($workers->isNotEmpty()) {
//
//            $contractors = $workers->unique('worker_id')->pluck('worker');
//
//            $children = collect();
//            foreach ($contractors as $contractor) {
//
//                $order_children = collect();
//                $contractor_workers = $workers->where('worker_id', $contractor->id)->all();
//
//                foreach ($contractor_workers as $contractor_worker) {
//                    $order_children->push([
//                        'id' => "worker_{$contractor_worker->id}",
//                        'worker_id' => trans('transbaza_contractor.contractors'),
//                        'title' => trans('transbaza_order.order') . " #{$contractor_worker->order_id}",
//                        'order_id' => $contractor_worker->order_id,
//                    ]);
//                }
//
//                $children->push([
//                    'id' => "my_contractors_{$contractor->id}",
//                    'title' => $contractor->company_name,
//                    'worker_id' => trans('transbaza_contractor.contractors'),
//                    'children' => $order_children
//                ]);
//            }
//
//
//            $v_collection = $v_collection->merge($children);
//        }
//        foreach ($workers as $worker) {
//
//            $collection->push([
//                'resourceId' => "worker_{$worker->id}",
//                'title' => /*trans('transbaza_order.order') .*/ " #{$worker->order_id}",
//                'start' => $worker->date_from->format('Y-m-d H:i'),
//                'end' => $worker->date_to->format('Y-m-d H:i'),
//                'order_id' => $worker->order_id,
//            ]);
//
//        }
        return [
            'vehicles' => $v_collection->toArray(),
            'calendar' => $collection->toArray()
        ];
    }

    function getCalendarStats2(Request $request): array
    {
        $request->validate([
            'date_from' => 'required|date',
            'date_to' => 'required|date',
        ]);

        $collection = collect();
        $v_collection = collect();

        $dateFrom = Carbon::parse($request->input('date_from'))->setTimezone(config('app.timezone'));
        $dateTo = Carbon::parse($request->input('date_to'))->setTimezone(config('app.timezone'));

        $vehicles = Machinery::query()->with(['base', '_type', 'model', 'brand', 'freeDays' => fn($q) => $q->forPeriod($dateFrom->startOfDay(), $dateTo->endOfDay())]);


        if ($request->filled('category_id')) {
            $vehicles->where('type', $request->input('category_id'));
        }

        if ($request->filled('base_id')) {
            $vehicles->where('base_id', $request->input('base_id'));
        }
        if (is_array($request->input('categories'))) {
            $vehicles->whereIn('type', Arr::flatten($request->input('categories')));
        }

        $leadsPositions = LeadPosition::query()
            ->select('*', DB::raw('DATE(date_from) as dt'))
            ->whereHas('lead', function ($q) use (
                $dateFrom,
                $dateTo
            ) {
                $q->forPeriod($dateFrom->startOfDay(), $dateTo->endOfDay());
            });

//        if ($request->filled('status')) {
//            switch ($request->status) {
//                case 'free':
//                    $vehicles->whereDoesntHave('freeDays', function (Builder $q) use ($dateFrom, $dateTo) {
//                        $q->forPeriod($dateFrom->startOfDay(), $dateTo->endOfDay(), false);
//                    });
//                    break;
//                case 'repair':
//                    $vehicles->whereHas('freeDays', function (Builder $q) use ($dateFrom, $dateTo) {
//                        $q->forPeriod($dateFrom->startOfDay(), $dateTo->endOfDay(), false)->whereHas('technicalWork');
//                    });
//                    break;
//                case 'order':
//                    $vehicles->whereHas('freeDays', function (Builder $q) use ($dateTo, $dateFrom) {
//                        $q->forPeriod($dateFrom->startOfDay(), $dateTo->endOfDay(), false)->whereHas('order');
//                    });
//                    break;
//            }
//        }
        $vehicles = $vehicles->forBranch()->get();
        $leads = [];
        if ($request->filled('status')) {
            if ($request->status == 'lead') {
                $leads = LeadPosition::query()
                    ->select('*', DB::raw('DATE(date_from) as dt'))
                    ->whereHas('lead', function ($q) use (
                        $dateFrom,
                        $dateTo
                    ) {
                        $q->forPeriod($dateFrom->startOfDay(), $dateTo->endOfDay());
                    });
            }
        }

        $calendar = collect();

        foreach ($vehicles as $vehicle) {

            $v_collection->push([
                'id' => $vehicle->id,
                'title' => $vehicle->name.($vehicle->base
                        ? " ({$vehicle->base->name})"
                        : ''),
                'category_id' => $vehicle->type,
                'category_name' => $vehicle->type_name,
                'board_number' => $vehicle->board_number,
                'model_id' => $vehicle->model_id,
                'model_name' => $vehicle->model
                    ? $vehicle->model->name
                    : '',
                'type' => $vehicle->machine_type,
                'worker_id' => trans('transbaza_machine_index.title'),
            ]);
            $calendar = $calendar->merge($vehicle->freeDays);
        }

        foreach ($leads as $lead) {
            $v_collection->push([
                'id' => $lead->id,
                'title' => $lead->lead->title,
                'category_id' => $lead->type_id,
                'category_name' => $lead->type_name,
                'model_id' => $lead->machinery_model_id,
                'model_name' => $lead->machinery_model_id ? $lead->model->name : '',
                'type' => $lead->model->machine_type,
                'worker_id' => trans('transbaza_machine_index.title'),
            ]);
            $calendar = $calendar->merge($vehicle->freeDays);
        }

//        foreach ($calendar->sortBy('machine_id') as $period) {
//
//            $collection->push([
//                'order_id' => $period->order_id,
//                'resourceId' => "{$period->machine_id}_{$period->order_id}",
//                'title' => $period->title,
//                'start' => $period->startDate->format('Y-m-d H:i'),
//                'end' => $period->endDate->format('Y-m-d H:i'),
//                'color' => $period->color,
//
//            ]);
//            $collection->push([
//                'order_id' => $period->order_id,
//                'resourceId' => $period->machine_id,
//                'title' => $period->title,
//                'start' => $period->startDate->format('Y-m-d H:i'),
//                'end' => $period->endDate->format('Y-m-d H:i'),
//                'color' => $period->color,
//                'manager' => $period->manager ? $period->manager->id_with_email : '',
//            ]);
//
//        }
//
//
//        $workers = OrderComponent::query()
//            ->where('worker_type', Contractor::class)
//            ->whereHas('order', function ($q) {
//                $q->contractorOrders();
//            })
//            ->get();
//
//        if ($workers->isNotEmpty()) {
//
//            $contractors = $workers->unique('worker_id')->pluck('worker');
//
//            $children = collect();
//            foreach ($contractors as $contractor) {
//
//                $order_children = collect();
//                $contractor_workers = $workers->where('worker_id', $contractor->id)->all();
//
//                foreach ($contractor_workers as $contractor_worker) {
//                    $order_children->push([
//                        'id' => "worker_{$contractor_worker->id}",
//                        'worker_id' => trans('transbaza_contractor.contractors'),
//                        'title' => trans('transbaza_order.order') . " #{$contractor_worker->order_id}",
//                        'order_id' => $contractor_worker->order_id,
//                    ]);
//                }
//
//                $children->push([
//                    'id' => "my_contractors_{$contractor->id}",
//                    'title' => $contractor->company_name,
//                    'worker_id' => trans('transbaza_contractor.contractors'),
//                    'children' => $order_children
//                ]);
//            }
//
//
//            $v_collection = $v_collection->merge($children);
//        }
//        foreach ($workers as $worker) {
//
//            $collection->push([
//                'resourceId' => "worker_{$worker->id}",
//                'title' => /*trans('transbaza_order.order') .*/ " #{$worker->order_id}",
//                'start' => $worker->date_from->format('Y-m-d H:i'),
//                'end' => $worker->date_to->format('Y-m-d H:i'),
//                'order_id' => $worker->order_id,
//            ]);
//
//        }
        return [
            'vehicles' => $v_collection->toArray(),
            'calendar' => $collection->toArray()
        ];
    }

    function getAttentionData()
    {
        $open_leads =
            Lead::query()->forDomain()->dispatcherLead()->forBranch()->whereStatus(Lead::STATUS_OPEN)->count();
        $in_work = Lead::query()->forDomain()->forBranch()->whereStatus(Lead::STATUS_ACCEPT)->count();

        $noPaidOrders = Order::query()
            ->whereDoesntHave('workers', function ($q) {
                $q->where('status', Order::STATUS_REJECT);
            })
            ->where('orders.contractor_id', $this->companyBranch->id)
            ->whereNotIn('orders.status', [
                Order::STATUS_OPEN,
                //Order::STATUS_CLOSE,
                Order::STATUS_HOLD,
            ])
            ->forDomain()
            ->whereHas('dispatcher_contractors')
            //->whereStatus(Order::STATUS_ACCEPT)

            ->withCount([
                'workers as workers_sum' => function ($query) {
                    $query->select(DB::raw('(SUM(order_workers.delivery_cost) + SUM(order_workers.amount))'));
                }
            ])
            ->withCount([
                'contractor_pays as contractors_sum' => function ($query) {
                    $query->select(DB::raw('SUM(dispatcher_contractor_pays.sum)'));
                }
            ])
            ->withCount([
                'valueAdded as value_added_sum' => function ($query) {

                    $query->select(DB::raw('SUM(order_workers_value_added.amount)'))
                        ->where('order_workers_value_added.owner_id', Auth::id());
                }
            ])
            ->where(function ($q) {

                $q->having('contractors_sum', '<', DB::raw('value_added_sum + workers_sum'));

                $q->raw(DB::raw('or not exists (select * from `dispatcher_contractor_pays` where `orders`.`id` = `dispatcher_contractor_pays`.`order_id`)'));
            })->get();

        $paidOrders = Order::query()
            ->where('orders.contractor_id', $this->companyBranch->id)
            ->whereStatus(Order::STATUS_ACCEPT)
            ->forDomain()
            ->whereHas('dispatcher_contractors')
            ->whereHas('contractor_pays')
            ->withCount([
                'workers as workers_sum' => function ($query) {
                    $query->select(DB::raw('(SUM(order_workers.delivery_cost) + SUM(order_workers.amount))'));
                }
            ])
            ->withCount([
                'contractor_pays as contractors_sum' => function ($query) {
                    $query->select(DB::raw('SUM(dispatcher_contractor_pays.sum)'));
                }
            ])
            ->withCount([
                'valueAdded as value_added_sum' => function ($query) {

                    $query->select(DB::raw('SUM(order_workers_value_added.amount)'))
                        ->where('order_workers_value_added.owner_id', Auth::id());
                }
            ])
            ->having('contractors_sum', '>', DB::raw('value_added_sum + workers_sum'))
            ->pluck('id');

        $inWorkOrders =
            Order::query()->where('orders.contractor_id',
                $this->companyBranch->id)->whereStatus(Order::STATUS_ACCEPT)->count();


        return response()->json([
            'open_leads' => $open_leads,
            'in_work_leads' => $in_work,
            'paid_orders' => $paidOrders,
            'no_paid_orders' => $noPaidOrders->pluck('id'),
            'in_work_orders' => $inWorkOrders,
        ]);

    }


    function getUtilizationData(Request $request)
    {

        $request->validate([
            'utilization' => 'nullable|array',
            'date_from' => 'required|date|before_or_equal:date_to',
            'date_to' => 'required|date|before_or_equal:'.now(),
        ]);
        /** @var Builder $vehicles */
        $vehicles = Machinery::query()->forDomain()->forBranch();

        $filter = new Filter($vehicles);

        $filter->getEqual([
            'category_id' => 'type',
            'brand_id' => 'brand_id',
            'model_id' => 'model_id',
        ])->getLike(['name' => 'name']);


        try {
            $date_from =
                Carbon::parse($request->input('date_from'))->setTimezone(config('app.timezone'))->startOfMonth();
            $date_to = Carbon::parse($request->input('date_to'))->setTimezone(config('app.timezone'))->endOfMonth();

        } catch (\Exception $exception) {
            return response()->json(['wrong_date_time'], 400);
        }
        $days = $date_from->diffInDays($date_to) + 1;
        if ($request->filled('utilization')) {
            $part = $request->input('utilization');
            $fromPercent = $part[0];
            $toPercent = $part[1];
        } else {
            $fromPercent = 0;
            $toPercent = 100;
        }
        $vehicles->withCount([
            'order_position as percent' => function ($q) use (
                $request,
                $days,
                $fromPercent,
                $toPercent,
                $date_from,
                $date_to
            ) {

                $q->forPeriod($date_from->copy(), $date_to->copy());
                if ($request->filled('manager_id')) {
                    $q->whereHas('order', function ($q) use (
                        $request
                    ) {
                        $q->forManager($request->input('manager_id'));
                    });
                }
                $q->select(DB::raw("SUM(order_workers.order_duration) * 100 / {$days}"));
                // ->where('order_type', TimeCalculation::TIME_TYPE_SHIFT)

            }
        ]);
        $months = CarbonPeriod::create($date_from, '1 month', $date_to);
        /** @var Carbon $month */
        $havingRawSql = '';
        foreach ($months as $k => $month) {
            $format = $month->format('Ym');
            $days = ($month->copy()->endOfMonth()->diffInDays($month->copy()->startOfMonth()) + 1);
            $vehicles->withCount([
                "order_position as {$format}percent" => function ($q) use (
                    $request,
                    $days,
                    $fromPercent,
                    $toPercent,
                    $month
                ) {

                    $q->forPeriod($month->copy()->startOfMonth(), $month->endOfMonth());

                    if ($request->filled('manager_id')) {
                        $q->whereHas('order', function ($q) use (
                            $request
                        ) {
                            $q->forManager($request->input('manager_id'));
                        });
                    }
                    $q->select(DB::raw("(case when SUM(order_workers.order_duration) > {$days} then {$days} else SUM(order_workers.order_duration) end)  * 100 / {$days}"));
                    // ->where('order_type', TimeCalculation::TIME_TYPE_SHIFT)

                }
            ]);
            $havingRawSql .= "({$format}percent >= {$fromPercent} and {$format}percent <= {$toPercent})";
            if (count($months) !== ($k + 1)) {
                $havingRawSql .= ' or ';
            }
        }
        $vehicles->havingRaw($havingRawSql);

        if ($request->filled('sortBy')) {
            $sort = $request->input('sortBy');
            $type =
                toBool($request->input('asc'))
                    ? 'asc'
                    : 'desc';

            switch ($sort) {
                case 'utilization_percent':
                    $vehicles->orderBy('percent', $type);
                    break;
                case 'name':
                    $vehicles->orderBy('name', $type);
                    break;
            }
        }


        $vehicles = $vehicles->get();

        $components = OrderComponent::query()
            ->where('worker_type', Machinery::class)
            ->whereIn('worker_id', $vehicles->pluck('id')->toArray());
        //      ->toArray());

        $components->whereHas('order', function ($q) use (
            $request
        ) {
            $q->forBranch();
            if ($request->filled('manager_id')) {
                $q->forManager($request->input('manager_id'));
            }
        });


        $components->forPeriod($date_from, $date_to, true);


        $components = $components->get();

        $utilization = collect();

        foreach ($components as $component) {

            $dates = FreeDay::generateDateRange($component->date_from, $component->date_to, true);

            /** @var Carbon $date */
            foreach ($dates as $date) {
                $date->startOfDay();


                if (!$date->betweenIncluded($date_from, $date_to)) {

                    //   logger($date_from  . ' ' . $date . ' '. $date_from->getTimestamp() . ' ' . $date->getTimestamp() . ' ' . $date_to);
                    continue;
                }
                $count = count($dates);

                if ($component->worker->change_hour === 24 && $count > 1) {
                    $count -= 1;
                }
                $utilization->push([
                    'id' => $component->worker_id,
                    'date' => $date,
                    'manager' => '',//($component->order->manager ? $component->order->manager->contact_person : ''),
                    'amount' => $component->amount / $count,
                ]);
            }
        }


        $total =
            Machinery::query()->forDomain()->where(function (Builder $q) use (
                $date_from,
                $date_to
            ) {
                $q->whereBetween('created_at', [$date_from, $date_to])
                    ->orWhere(function ($q) use (
                        $date_from,
                        $date_to
                    ) {
                        $q->where('created_at', '<=', $date_from)
                            ->where('created_at', '<=', $date_to);
                    });
            })->forBranch()->count();

        $totalMonthData = [];
        foreach ($months as $month) {
            $format = $month->format('Ym');
            if ((int) $month->format('Ym') > (int) $date_to->format('Ym')) {
                break;
            }
            $monthUtil =
                $utilization->filter(function ($item) use (
                    $month,
                    &
                    $totalMonthData
                ) {

                    return $item['date']->format('Ym') === $month->format('Ym');
                });

            $totalMonthData[$month->format('Ym')] = [
                'amount' => $monthUtil->sum('amount'),
                'days' => $monthUtil->count()
            ];

            foreach ($vehicles as $vehicle) {
                if (!isset($vehicle->util)) {
                    $vehicle->util = new \stdClass();
                }
                $vehicle->util->{$format.'_amount'} = $monthUtil->where('id', $vehicle->id)->sum('amount');
                $vehicle->util->{$format.'_days'} = $monthUtil->where('id', $vehicle->id)->count();
            }
        }
        $utilization = $utilization->map(function ($ut) {
            $ut['date'] = $ut['date']->format('Y-m-d H:i:s');
            return $ut;
        });
        return response()->json(
            [
                'total_vehicles' => $total,
                'vehicles' => $vehicles,
                'utilization' => ($request->filled('items')
                    ? $utilization
                    : []),
                'total' => $totalMonthData
            ]
        );
    }

    function getRejectStats(Request $request)
    {
        $request->validate([
            'date_from' => 'required|date',
            'date_to' => 'required|date',
        ]);

        $date_from = Carbon::parse($request->input('date_from'))->startOfMonth();
        $date_to = Carbon::parse($request->input('date_to'))->endOfMonth();

        $calls = TelephonyCallHistory::query()->forCompany()->whereBetween('created_at', [$date_from, $date_to]);

        if ($this->companyBranch->mailConnector) {
            $mCounter = $this->companyBranch->mailConnector->getMails([
                'count' => 1,
                'date_from' => $date_from,
                'date_to' => $date_to,
            ]);
            $counterNull = $this->companyBranch->mailConnector->getMails([
                'getCount' => 1,
                'date_from' => $date_from,
                'date_to' => $date_to,
            ]);
        }

        $orders = Order::query()->forBranch()->contractorOrders()->whereBetween('created_at', [$date_from, $date_to]);

        $leadsQuery = Lead::query()
            ->forBranch()
            ->whereBetween('created_at', [$date_from, $date_to]);
        $leadSourceQuery = clone $leadsQuery;
        $ordersSourceQuery = clone $orders;

        $preLeads = PreLead::query()
            ->forBranch()
            ->whereBetween('created_at', [$date_from, $date_to]);

        if ($request->filled('manager_id')) {
            $leadsQuery->forManager($request->input('manager_id'));
            $preLeads->forManager($request->input('manager_id'));
        }


        return response()->json([
            'calls' => [
                'total' => $calls->count(),
                'rejects' => $calls->whereNull('bind_type')->count(),
            ],
            'mails' => [
                'total' => ($mCounter ?? 0),
                'rejects' => ($counterNull ?? 0),
            ],
            'preleads' => [
                'total' => $preLeads->count(),
                'rejects' => $preLeads->whereNotNull('reject_type')->count(),
            ],

            'leads' => [
                'total' => $leadsQuery->count(),
                'rejects' => $leadsQuery->whereNotNull('reject_type')->count(),
            ],

            'orders' => [
                'total' => $orders->count(),
                'rejects' => $orders->rejected()->count(),
            ],
            'sources' => [
                'leads' => [
                    'crm' => Lead::query()
                        ->forBranch()
                        ->whereBetween('created_at', [$date_from, $date_to])
                        ->where(function (Builder $q) {

                            $q->whereNull('source');
                        })
                        ->count(),
                    'marketplace' => Lead::query()
                        ->forBranch()
                        ->whereBetween('created_at', [$date_from, $date_to])->whereSource(Lead::SOURCE_TB)->count(),
                    'mail' => Lead::query()
                        ->forBranch()
                        ->whereBetween('created_at', [$date_from, $date_to])->whereSource(Lead::SOURCE_MAIL)->count(),
                    'call' => Lead::query()
                        ->forBranch()
                        ->whereBetween('created_at', [$date_from, $date_to])->whereSource(Lead::SOURCE_CALL)->count(),
                    'whatsapp' => Lead::query()
                        ->forBranch()
                        ->whereBetween('created_at',
                            [$date_from, $date_to])->whereSource(Lead::SOURCE_WHATSAPP)->count(),
                ],
                'orders' => [
                    'crm' => Order::query()->forBranch()->contractorOrders()->whereBetween('created_at',
                        [$date_from, $date_to])
                        ->whereHas('leads', function (Builder $q) {
                            $q->whereNull('source');
                        })->count(),
                    'marketplace' => Order::query()->forBranch()->contractorOrders()->whereBetween('created_at',
                        [$date_from, $date_to])->whereHas('leads', function (
                        $q
                    ) {
                        $q->whereIn('dispatcher_leads.source', [Lead::SOURCE_TB]);
                    })->count(),
                    'mail' => Order::query()->forBranch()->contractorOrders()->whereBetween('created_at',
                        [$date_from, $date_to])->whereHas('leads', function (
                        $q
                    ) {
                        $q->whereIn('dispatcher_leads.source', [Lead::SOURCE_MAIL]);
                    })->count(),
                    'call' => Order::query()->forBranch()->contractorOrders()->whereBetween('created_at',
                        [$date_from, $date_to])->whereHas('leads', function (
                        $q
                    ) {
                        $q->whereIn('dispatcher_leads.source', [Lead::SOURCE_CALL]);
                    })->count(),
                    'whatsapp' => Order::query()->forBranch()->contractorOrders()->whereBetween('created_at',
                        [$date_from, $date_to])->whereHas('leads', function (
                        $q
                    ) {
                        $q->whereIn('dispatcher_leads.source', [Lead::SOURCE_WHATSAPP]);
                    })->count(),
                ],
                'finished' => [
                    'crm' => Order::query()->forBranch()
                        ->contractorOrders()
                        ->whereBetween('created_at', [$date_from, $date_to])
                        ->whereStatus(Order::STATUS_DONE)
                        ->whereNull('source')->count(),

                    'marketplace' => Order::query()->forBranch()->contractorOrders()->whereBetween('created_at',
                        [$date_from, $date_to])->whereStatus(Order::STATUS_DONE)->whereSource(Lead::SOURCE_TB)->count(),
                    'mail' => Order::query()->forBranch()->contractorOrders()->whereBetween('created_at', [
                        $date_from, $date_to
                    ])->whereStatus(Order::STATUS_DONE)->whereSource(Lead::SOURCE_MAIL)->count(),
                    'call' => Order::query()->forBranch()->contractorOrders()->whereBetween('created_at', [
                        $date_from, $date_to
                    ])->whereStatus(Order::STATUS_DONE)->whereSource(Lead::SOURCE_CALL)->count(),
                    'whatsapp' => Order::query()->forBranch()->contractorOrders()->whereBetween('created_at', [
                        $date_from, $date_to
                    ])->whereStatus(Order::STATUS_DONE)->whereSource(Lead::SOURCE_WHATSAPP)->count(),
                ],
            ]

        ]);
    }

    function getWarehouseReport(Request $request)
    {
        $request->validate([
            'date_from' => 'required|date|before_or_equal:date_to',
            'date_to' => 'required|date|before_or_equal:'.now(),
        ]);

        /** @var Builder $items */
        $items = Item::query()->forBranch();
        $items = $items->groupBy('stock_id')->get();
        $items =
            $items->map(function ($item) use (
                $request
            ) {
                $item->available_on_end = $item->getSameCount(true, Carbon::parse($request->input('date_to')));
                $item->available_on_start = $item->getSameCount(true, Carbon::parse($request->input('date_from')));
                $item->posting =
                    $item->getSameCount(true, Carbon::parse($request->input('date_to')),
                        [Posting::class]) - $item->available_on_start;
                $item->sales = $item->available_on_start + $item->posting - $item->available_on_end;
                return $item;
            });

        return response()->json($items);
    }

    function getCashBox(Request $request)
    {
        $request->validate([
            'date_from' => 'required|date|before_or_equal:date_to',
            'date_to' => 'required|date|before_or_equal:'.now()->endOfYear(),
            'new_contracts' => 'nullable',
        ]);
        $dateFrom = Carbon::parse($request->input('date_from'))->setTimezone(config('app.timezone'))->startOfDay();
        $dateTo = Carbon::parse($request->input('date_to'))->setTimezone(config('app.timezone'))->endOfDay();
        $newContracts = $request->boolean('new_contracts');

        $monthRange = FreeDay::generateMonthRange($dateFrom->clone(), $dateTo->clone());
        $monthRange = collect($monthRange)->map(function (Carbon $item) {
            return $item->format('F');
        });

        $ordersQuery =
            InvoicePay::query()->with('invoice')->whereHasMorph('invoice', [DispatcherInvoice::class],
                function ($q) use (
                    $request, $newContracts, $dateFrom, $dateTo
                ) {
                    $q->where('type', '!=', 'pledge')->forBranch()->whereIn('owner_type', [Lead::class, Order::class]);
                    if ($request->filled('machinery_base_id') && $request->input('machinery_base_id') !== 'all') {
                        $q->whereHasMorph('owner', Order::class, function ($q) use ($request, $newContracts, $dateFrom, $dateTo) {
                             $q->where('machinery_base_id', request()->input('machinery_base_id'))
                             ->when($newContracts,
                                 fn(Builder $builder) => $builder->whereHas('customer.contracts', fn(Builder $contractsBuilder) => $contractsBuilder->whereBetween('created_at', [$dateFrom, $dateTo]))
                             );
                        });
                    }
                });

        $serviceQuery =
            InvoicePay::query()->with('invoice')->whereHasMorph('invoice', [DispatcherInvoice::class], function ($q) use ($request, $newContracts, $dateTo, $dateFrom) {
                $q->forBranch();

                    $q->whereHasMorph('owner', ServiceCenter::class, function ($q) use ($request, $newContracts, $dateFrom, $dateTo) {
                        $q->when($newContracts,
                            fn(Builder $builder) => $builder->whereHasMorph('customer', [Customer::class],
                                fn(Builder $customerBuilder) => $customerBuilder->whereHas('contracts', fn(Builder $contractsBuilder) => $contractsBuilder->whereBetween('created_at', [$dateFrom, $dateTo])))
                        );
                        if ($request->filled('machinery_base_id')) {
                            $request->input('machinery_base_id') === 'all' ? $q : $q->where('base_id',
                                $request->input('machinery_base_id'));;

                        }
                    });

            });

        $partsQuery =
            InvoicePay::query()->with('invoice')->whereHasMorph('invoice', [DispatcherInvoice::class], function ($q) {
                $q->forBranch()->whereIn('owner_type', [PartsSale::class]);
            });

        $startInvoiceCash =
            InvoicePay::query()->with('invoice')->whereHasMorph('invoice', [DispatcherInvoice::class], function ($q) use ($newContracts, $dateFrom, $dateTo) {
                $q->forBranch()->when($newContracts, fn(Builder $builder) => $builder->whereHasMorph('owner', Order::class, function ($q) use ($newContracts, $dateFrom, $dateTo) {
                    $q->when($newContracts,
                        fn(Builder $builder) => $builder->whereHas('customer.contracts', fn(Builder $contractsBuilder) => $contractsBuilder->whereBetween('created_at', [$dateFrom, $dateTo]))
                    );
                }));
            })->where('date', '<', $dateFrom)->getSum();

        $endInvoiceCash =
            InvoicePay::query()->with('invoice')->whereHasMorph('invoice', [DispatcherInvoice::class], function ($q) use ($newContracts, $dateTo, $dateFrom) {
                $q->forBranch()
                    ->when($newContracts, fn(Builder $builder) => $builder->whereHasMorph('owner', Order::class, function ($q) use ($newContracts, $dateFrom, $dateTo) {
                    $q->when($newContracts,
                            fn(Builder $builder) => $builder->whereHas('customer.contracts', fn(Builder $contractsBuilder) => $contractsBuilder->whereBetween('created_at', [$dateFrom, $dateTo]))
                        );
                }));
            })->where('date', '<', $dateTo)->getSum();

        $outPartsQuery = Posting::query()->forBranch()
            ->withCount([
                'stockItems as sm' => function ($q) {
                    $q->select(DB::raw('SUM(`cost_per_unit` * `amount`)'));
                }
            ]);

        $cashOrderPaysStartPeriod =
            (clone $ordersQuery)->where('type', 'cash')->where('date', '<', $dateFrom)->getSum();


        $cashlessOrderPaysStartPeriod =
            (clone $ordersQuery)->where('type', 'cashless')->where('date', '<', $dateFrom)->getSum();


        $cashOrderPaysOnCardStartPeriod =
            (clone $ordersQuery)->where('type', 'cash')->whereIn('method', ['card', 'bank'])->where('date', '<',
                $dateFrom)->getSum();


        $cashlessOrderPaysOnCardStartPeriod =
            (clone $ordersQuery)->where('type', 'cashless')->whereIn('method', ['card', 'bank'])->where('date', '<',
                $dateFrom)->getSum();

        $cashOrderPaysOnPledgeStartPeriod =
            (clone $ordersQuery)->where('type', 'cash')->where('method', 'pledge')->where('date', '<',
                $dateFrom)->getSum();


        $cashlessOrderPaysOnPledgeStartPeriod =
            (clone $ordersQuery)->where('type', 'cashless')->where('method', 'pledge')->where('date', '<',
                $dateFrom)->getSum();

        $cashOrderPaysInPeriod =
            (clone $ordersQuery)->where('type', 'cash')->whereBetween('date', [$dateFrom, $dateTo])->getSum();

        $cashOrderPaysInPeriodVat =
            (clone $ordersQuery)->where('type', 'cash')->whereBetween('date', [$dateFrom, $dateTo])->getSumVat();

        $cashlessOrderPaysInPeriod =
            (clone $ordersQuery)->where('type', 'cashless')->whereBetween('date', [$dateFrom, $dateTo])->getSum();

        $cashlessOrderPaysInPeriodVat =  (clone $ordersQuery)->where('type', 'cashless')->whereBetween('date', [$dateFrom, $dateTo])->getSumVat();

        $cashOrderPaysOnCardInPeriod =
            (clone $ordersQuery)->where('type', 'cash')->whereIn('method', ['card', 'bank'])->whereBetween('date',
                [$dateFrom, $dateTo])->getSum();
        $cashlessOrderPaysOnCardInPeriod =
            (clone $ordersQuery)->where('type', 'cashless')->whereIn('method', ['card', 'bank'])->whereBetween('date',
                [$dateFrom, $dateTo])->getSum();


        $cashOrderPaysOnPledgeInPeriod =
            (clone $ordersQuery)->where('type', 'cash')->where('method', 'pledge')->whereBetween('date',
                [$dateFrom, $dateTo])->getSum();
        $cashlessOrderPaysOnPledgeInPeriod =
            (clone $ordersQuery)->where('type', 'cashless')->where('method', 'pledge')->whereBetween('date',
                [$dateFrom, $dateTo])->getSum();


        $cashPaysPartsStartPeriod =
            (clone $partsQuery)->where('type', 'cash')->where('date', '<', $dateFrom)->getSum();

        $cashlessPaysPartsStartPeriod =
            (clone $partsQuery)->where('type', 'cashless')->where('date', '<', $dateFrom)->getSum();

        $cashlessPaysPartsInPeriod =
            (clone $partsQuery)->where('type', 'cashless')->whereBetween('date', [$dateFrom, $dateTo])->getSum();

        $cashPaysPartsInPeriod =
            (clone $partsQuery)->where('type', 'cash')->whereBetween('date', [$dateFrom, $dateTo])->getSum();


        $cashPaysServiceStartPeriod =
            (clone $serviceQuery)->where('type', 'cash')->where('date', '<', $dateFrom)->getSum();

        $cashlessPaysServiceStartPeriod =
            (clone $serviceQuery)->where('type', 'cashless')->where('date', '<', $dateFrom)->getSum();

        $cashlessPaysServiceInPeriod =
            (clone $serviceQuery)->where('type', 'cashless')->whereBetween('date', [$dateFrom, $dateTo])->getSum();

        $cashlessPaysServiceInPeriodVat = (clone $serviceQuery)->where('type', 'cashless')->whereBetween('date', [$dateFrom, $dateTo])->getSumVat();

        $cashPaysServiceInPeriod =
            (clone $serviceQuery)->where('type', 'cash')->whereBetween('date', [$dateFrom, $dateTo])->getSum();

        $cashPaysServiceInPeriodVat =  (clone $serviceQuery)->where('type', 'cash')->whereBetween('date', [$dateFrom, $dateTo])->getSum();
        /* ->whereBetween('date', [$dateFrom, $dateTo])*/

        $outPartsCashEndPeriod =
            (clone $outPartsQuery)->where('date', '<', $dateTo)->where('pay_type', 'cash')->get()->sum('sm');

        $outPartsCashLessEndPeriod =
            (clone $outPartsQuery)->where('date', '<', $dateTo)->where('pay_type', '!=', 'cash')->get()->sum('sm');

        $outPartsCashStartPeriod =
            (clone $outPartsQuery)->where('date', '<', $dateFrom)->where('pay_type', 'cash')->get()->sum('sm');

        $outPartsCashLessStartPeriod =
            (clone $outPartsQuery)->where('date', '<', $dateFrom)->where('pay_type', '!=', 'cash')->get()->sum('sm');

        $cashStartQuery = CashRegister::query()->forBranch()->withoutPays()->where('created_at', '<',
            $dateFrom)->whereType('in');

        $cashStartQuery->when(request()->input('machinery_base_id'), function (
            $query,
            $baseId
        ) {
            return request()->input('machinery_base_id') === 'all' ? $query->whereNotNull('machinery_base_id') : $query->where('machinery_base_id',
                $baseId);
        });

        $cashStart = (clone $cashStartQuery)->where('stock', '=', 'cash')->sum('sum');
        $cashlessStart = (clone $cashStartQuery)->where('stock', '!=', 'cash')->sum('sum');

        $cashOutStartQuery = CashRegister::query()->forBranch()->withoutPays()->where('created_at', '<',
            $dateFrom)->whereType('out');

        $cashOutStartQuery->when(request()->input('machinery_base_id'), function (
            $query,
            $baseId
        ) {
            return request()->input('machinery_base_id') === 'all' ? $query->whereNotNull('machinery_base_id') : $query->where('machinery_base_id',
                $baseId);
        });

        $cashOutStart = (clone $cashOutStartQuery)->where('stock', '=', 'cash')->sum('sum');
        $cashlessOutStart = (clone $cashOutStartQuery)->where('stock', '!=', 'cash')->sum('sum');


        $cashEnd = CashRegister::query()->forBranch()->withoutPays()->when(request()->input('machinery_base_id'),
            function (
                $query,
                $baseId
            ) {
                return request()->input('machinery_base_id') === 'all' ? $query->whereNotNull('machinery_base_id') : $query->where('machinery_base_id',
                    $baseId);
            })->where('created_at', '<=', $dateTo)
            ->whereType('in')
            ->where('stock', 'cash')
            ->sum('sum');

        $cashlessEnd = CashRegister::query()->forBranch()->withoutPays()->when(request()->input('machinery_base_id'),
            function (
                $query,
                $baseId
            ) {
                return request()->input('machinery_base_id') === 'all' ? $query->whereNotNull('machinery_base_id') : $query->where('machinery_base_id',
                    $baseId);
            })->where('created_at', '<=', $dateTo)
            ->whereType('in')
            ->where('stock', '!=', 'cash')
            ->sum('sum');

        $cashlessOutEnd = CashRegister::query()->forBranch()->withoutPays()->when(request()->input('machinery_base_id'),
            function (
                $query,
                $baseId
            ) {
                return request()->input('machinery_base_id') === 'all' ? $query->whereNotNull('machinery_base_id') : $query->where('machinery_base_id',
                    $baseId);
            })->where('created_at', '<=', $dateTo)
            ->where('stock', '!=', 'cash')
            ->whereType('out')
            ->sum('sum');

        $cashOutEnd = CashRegister::query()->forBranch()->withoutPays()->when(request()->input('machinery_base_id'),
            function (
                $query,
                $baseId
            ) {
                return request()->input('machinery_base_id') === 'all' ? $query->whereNotNull('machinery_base_id') : $query->where('machinery_base_id',
                    $baseId);
            })->where('created_at', '<=', $dateTo)
            ->where('stock', 'cash')
            ->whereType('out')
            ->sum('sum');

        $cashCurrent = CashRegister::query()->forBranch()->withoutPays()->when(request()->input('machinery_base_id'),
            function (
                $query,
                $baseId
            ) {
                return request()->input('machinery_base_id') === 'all' ? $query->whereNotNull('machinery_base_id') : $query->where('machinery_base_id',
                    $baseId);
            })->whereBetween('created_at', [$dateFrom, $dateTo]);

        $budgets = Budget::query()->forBranch()->whereIn('month', $monthRange->toArray())->get();

        $currentCashInStartPeriod = ($cashStart) - ($cashOutStart);
        $currentCashlessInStartPeriod = $cashlessStart - $cashlessOutStart;
        $currentCashEndPeriod = $cashEnd - $cashOutEnd;
        $currentCashlessEndPeriod = $cashlessEnd - $cashlessOutEnd;

        $baseCashStartPeriod =
            ($cashOrderPaysStartPeriod + $cashPaysServiceStartPeriod + $currentCashInStartPeriod);

        $baseCashlessStartPeriod =
            ($cashlessOrderPaysStartPeriod + $cashlessPaysServiceStartPeriod + $currentCashlessInStartPeriod);

        $baseCashEndPeriod =
            $baseCashStartPeriod + $cashOrderPaysInPeriod + $cashPaysServiceInPeriod
            + ($currentCashEndPeriod - $currentCashInStartPeriod);


        $baseCashlessEndPeriod =
            $baseCashlessStartPeriod + $cashlessOrderPaysInPeriod + $cashlessPaysServiceInPeriod
            + ($currentCashlessEndPeriod - $currentCashlessInStartPeriod);

        $withdrawalQuery  = CashRegister::query()->forBranch()->when(request()->input('machinery_base_id'),
            function (
                $query,
                $baseId
            ) {
                return request()->input('machinery_base_id') === 'all' ? $query->whereNotNull('machinery_base_id') : $query->where('machinery_base_id',
                    $baseId);
            });
        $withdrawal = (clone $withdrawalQuery)->whereBetween('created_at', [$dateFrom, $dateTo])->where('ref', 'like', '%withdrawal%')
        ->where('type', 'in')->sum('sum');

        $withdrawalStartPeriod = (clone $withdrawalQuery)->where('created_at', '<', $dateFrom)->where('ref', 'like', '%withdrawal%')
            ->where('type', 'in')->sum('sum');
        return [
            'withdrawal' => $withdrawal,
            'cashRentPaysStartPeriod' => $cashOrderPaysStartPeriod,
            'cashlessRentPaysStartPeriod' => $cashlessOrderPaysStartPeriod,

            'cashRentPaysOnCardStartPeriod' => $cashOrderPaysOnCardStartPeriod - $withdrawalStartPeriod,
            'cashlessRentPaysOnCardStartPeriod' => $cashlessOrderPaysOnCardStartPeriod,

            'cashRentPaysOnPledgeStartPeriod' => $cashOrderPaysOnPledgeStartPeriod,
            'cashlessRentPaysOnPledgeStartPeriod' => $cashlessOrderPaysOnPledgeStartPeriod,
            'cashRentPaysOnPledgeInPeriod' => $cashOrderPaysOnPledgeInPeriod,
            'cashlessRentPaysOnPledgeInPeriod' => $cashlessOrderPaysOnPledgeInPeriod,

            'cashRentPaysInPeriod' => $cashOrderPaysInPeriod,
            'cashRentPaysInPeriodVat' => $cashOrderPaysInPeriodVat,
            'cashlessRentPaysInPeriod' => $cashlessOrderPaysInPeriod,
            'cashlessRentPaysInPeriodVat' => $cashlessOrderPaysInPeriodVat,

            'cashRentPaysOnCardInPeriod' => $cashOrderPaysOnCardInPeriod,
            'cashlessRentPaysOnCardInPeriod' => $cashlessOrderPaysOnCardInPeriod,

            'outPartsCashEndPeriod' => $outPartsCashEndPeriod,
            'outPartsCashLessEndPeriod' => $outPartsCashLessEndPeriod,
            'outPartsCashStartPeriod' => $outPartsCashStartPeriod,
            'outPartsCashLessStartPeriod' => $outPartsCashLessStartPeriod,

            'cashPaysPartsStartPeriod' => $cashPaysPartsStartPeriod,
            'cashlessPaysPartsStartPeriod' => $cashlessPaysPartsStartPeriod,
            'cashPaysPartsInPeriod' => $cashlessPaysPartsInPeriod,
            'cashlessPartsPaysInPeriod' => $cashPaysPartsInPeriod,

            'cashBoxUpPaysInPeriod' => (clone $cashCurrent)->where('type', 'in')->where('stock', '<>',
                'cashless')->sum('sum'),
            'cashlessBoxUpPaysInPeriod' => (clone $cashCurrent)->where('type', 'in')->where('stock',
                'cashless')->sum('sum'),
            'cashBoxDownPaysInPeriod' => (clone $cashCurrent)->where('type', 'out')->where('stock', '<>',
                'cashless')->sum('sum'),
            'cashlessBoxDownPaysInPeriod' => (clone $cashCurrent)->where('type', 'out')->where('stock',
                'cashless')->sum('sum'),

            'cashPaysServiceStartPeriod' => $cashPaysServiceStartPeriod,
            'cashlessPaysServiceStartPeriod' => $cashlessPaysServiceStartPeriod,
            'cashlessPaysServiceInPeriod' => $cashlessPaysServiceInPeriod,
            'cashlessPaysServiceInPeriodVat' => $cashlessPaysServiceInPeriodVat,
            'cashPaysServiceInPeriod' => $cashPaysServiceInPeriod,
            'cashPaysServiceInPeriodVat' => $cashPaysServiceInPeriodVat,

            'cashStart' => $cashStart + $cashlessStart + $startInvoiceCash - $outPartsCashStartPeriod - $outPartsCashLessStartPeriod - $cashOutStart - $cashlessOutStart,
            'cashEnd' => $cashEnd + $endInvoiceCash - $outPartsCashEndPeriod - $outPartsCashLessEndPeriod - $cashOutEnd,
            'cashCurrent' => (clone $cashCurrent)->where('type', 'in')->sum('sum') - (clone $cashCurrent)->where('type',
                    'out')->sum('sum'),
            'cashInPeriod' => $cashCurrent->get(),

            'cashPaysStartPeriod' => $baseCashStartPeriod,
            'cashlessPaysStartPeriod' => $baseCashlessStartPeriod,
            'cashPaysEndPeriod' => $baseCashEndPeriod,
            'cashlessPaysEndPeriod' => $baseCashlessEndPeriod,

            'rentBudget' => $budgets->where('type', 'rent')->sum('sum'),
            'partsBudget' => $budgets->where('type', 'parts_sale')->sum('sum'),
            'serviceBudget' => $budgets->where('type', 'service')->sum('sum')

        ];
    }
}
