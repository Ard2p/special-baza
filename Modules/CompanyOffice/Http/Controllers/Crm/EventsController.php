<?php

namespace Modules\CompanyOffice\Http\Controllers\Crm;

use App\Machines\FreeDay;
use App\Service\Google\CalendarService;
use App\Service\RequestBranch;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Modules\CompanyOffice\Entities\Company\GoogleCalendar;
use Modules\CompanyOffice\Entities\Employees\EmployeeTask;
use Modules\CompanyOffice\Entities\Employees\EmployeeTaskOperation;
use Modules\CompanyOffice\Services\CompanyRoles;
use Modules\CompanyOffice\Transformers\Events\LeadEvents;
use Modules\CompanyOffice\Transformers\Events\OrderEvents;
use Modules\CompanyOffice\Transformers\Events\PreLeadEvents;
use Modules\CompanyOffice\Transformers\Events\TaskEvents;
use Modules\CompanyOffice\Transformers\MachineryEvent;
use Modules\ContractorOffice\Transformers\MachineryLeadEvent;
use Modules\Dispatcher\Entities\Lead;
use Modules\Dispatcher\Entities\LeadPosition;
use Modules\Dispatcher\Entities\PreLead;
use Modules\Orders\Entities\Order;
use Modules\Orders\Entities\OrderComponent;

class EventsController extends Controller
{

    private $currentBranch;

    public function __construct(Request $request, RequestBranch $companyBranch)
    {
        $this->currentBranch = $companyBranch->companyBranch;


        $block = $this->currentBranch->getBlockName(CompanyRoles::BRANCH_CLIENTS);
        $this->middleware("accessCheck:{$block},".CompanyRoles::ACTION_SHOW)->only(
            [
                'index',
                'show',
                'destroy',
            ]);
        $this->middleware("accessCheck:{$block},".CompanyRoles::ACTION_UPDATE)->only(
            [
                'store',
                'update',
                'destroy',
            ]);


    }

    function getByResources(Request $request)
    {
        $events = collect();

        $dateFrom = Carbon::parse($request->input('date_from'))->setTimezone(config('app.timezone'));
        $dateTo = Carbon::parse($request->input('date_to'))->setTimezone(config('app.timezone'));

        $vehicleEvents = FreeDay::query()
            ->with(['technicalWork.serviceCenter'])
            ->forBranch()
            ->forPeriod($dateFrom->copy(), $dateTo->copy())
            ->whereNull('order_component_id')
            ->get();

        $components = OrderComponent::query()->with('order', 'worker')->whereHas('order', function (Builder $q) {
            $q->forBranch();
        })
            ->accepted()
            ->forPeriod($dateFrom->copy(), $dateTo->copy())
            ->get();
        foreach ($components as $component) {
            $events->push([
                'resourceId' => $component->worker_id,
                'resource_status' => $component->status,
                'resource_timestamps' => $component->worker->order_timestamps()->where('order_id',
                    $component->order_id)->get(),
                'finish_date' => $component->finish_date,
                'title' => /*trans('transbaza_order.order') .*/ $component->order->name,
                'start' => $component->date_from->format('Y-m-d H:i'),
                'end' => $component->date_to->format('Y-m-d H:i'),
                'date_from' => $component->date_from->format('Y-m-d H:i'),
                'date_to' => $component->date_to->format('Y-m-d H:i'),
                'order_id' => $component->order_id,
                'status' => $component->order->status_lang,
                'editable' => false,
                'order_amount' => $component->order->amount,
                'customer' => $component->order->customer,
                'address' => $component->order->address,
                'manager' => $component->order->manager,
                'type' => 'order',
            ]);
        }
        foreach ($vehicleEvents as $event) {
            $ev = \Modules\ContractorOffice\Transformers\MachineryEvent::make($event);
            $events->push($ev);
        }

        $leads = LeadPosition::query()
            ->select('*', DB::raw('DATE(date_from) as dt'))
            ->whereHas('lead', function ($q) use (
                $dateFrom,
                $dateTo
            ) {
                $q->forPeriod($dateFrom->startOfDay(), $dateTo->endOfDay());
            })->get();

        foreach ($leads as $leadPosition) {
            if (!$leadPosition->vehicles->isEmpty()) {
                foreach ($leadPosition->vehicles as $vehicle) {
                    $ld = [
                        'id' => $leadPosition->lead->id,
                        'internal_number' => $leadPosition->internal_number,
                        'date_from' => $leadPosition->date_from->format('Y-m-d H:i'),
                        'date_to' => $leadPosition->date_to->format('Y-m-d H:i'),
                        'start' => (string) $leadPosition->date_from,
                        'dt' => (string) $leadPosition->dt,
                        'end' => (string) $leadPosition->date_to,
                        'type' => 'lead',
                        'title' => trans('transbaza_order.reserve')." #{$leadPosition->lead->internal_number}",
                        'event_title' => trans('transbaza_order.reserve'),
                        'color' => '#ffb021',
                        'manager' => $leadPosition->lead->manager,

                        'customer' => $leadPosition->lead->customer,
                        'resourceId' => $vehicle->id,
                        'category_id' => $leadPosition->type_id,
                        'category_name' => $leadPosition->category->name,
                        'status' => $leadPosition->lead->status,
                        'editable' => false,
                        'address' => $leadPosition->lead->address,
                    ];
                    $events->push($ld);
                }
            } else {
                $ld = [
                    'id' => $leadPosition->lead->id,
                    'internal_number' => $leadPosition->internal_number,
                    'date_from' => $leadPosition->date_from->format('Y-m-d H:i'),
                    'date_to' => $leadPosition->date_to->format('Y-m-d H:i'),
                    'start' => (string) $leadPosition->date_from,
                    'dt' => (string) $leadPosition->dt,
                    'end' => (string) $leadPosition->date_to,
                    'type' => 'lead',
                    'title' => trans('transbaza_order.reserve')." #{$leadPosition->lead->internal_number}",
                    'event_title' => trans('transbaza_order.reserve'),
                    'color' => '#ffb021',
                    'manager' => $leadPosition->lead->manager,

                    'customer' => $leadPosition->lead->customer,
                    'resourceId' => null,
                    'category_id' => $leadPosition->type_id,
                    'category_name' => $leadPosition->category->name,
                    'status' => $leadPosition->lead->status,
                    'editable' => false,
                    'address' => $leadPosition->lead->address,
                ];
                $events->push($ld);
            }
        }

        $events = $events->map(function ($item, $key) {
            $item['ev_id'] = "ev-{$key}";

            return $item;
        });

        return $events;
    }

    /**
     * Display a listing of the resource.
     * @return Response
     */
    public function index(Request $request)
    {
        $request->validate([
            'date_from' => 'required|date',
            'date_to' => 'required|date',
        ]);

        if ($request->filled('timeline')) {
            return $this->getByResources($request);
        }
        if ($request->filled('dayCounter')) {
            return $this->getEventsCountByDay($request);

        }

        $events = collect();

        $dateFrom = Carbon::parse($request->input('date_from'))->setTimezone(config('app.timezone'));
        $dateTo = Carbon::parse($request->input('date_to'))->setTimezone(config('app.timezone'));

        /** @var Builder $tasks */
        $tasks = EmployeeTask::query()->forBranch();

        $tasks = $tasks->where(function (Builder $q) use ($dateFrom, $dateTo) {
            return $q->where('employee_id', \Auth::id())
                ->orWhere('responsible_id', \Auth::id());
        });

        if ($request->filled('status_id')) {
            $tasks = $tasks->where('status', $request->status_id);
        }

        if ($request->filled('customer_id')) {
            $tasks = $tasks->whereHas('customers', function ($q) use ($request) {
                return $q->where('id', $request->customer_id);
            });
        }

        if ($request->filled('order_id')) {
            $tasks = $tasks->whereHas('orders', function ($q) use ($request) {
                return $q->where('id', $request->order_id);
            });
        }
        $tasks = $tasks->forPeriod($dateFrom->copy(), $dateTo->copy());

        /** @var Builder $orders */
        $orders = Order::query()->with('customer')->forPeriod($dateFrom->copy(), $dateTo->copy())->forBranch();

        /** @var Builder $leads */
        $leads = Lead::query()->forPeriod($dateFrom->copy(), $dateTo->copy())->forBranch();

        /** @var Builder $preLeads */
        $preLeads = PreLead::query()->whereIn('status', [
            PreLead::STATUS_OPEN,
        ])->forPeriod($dateFrom->copy(), $dateTo->copy())->forBranch();

        /** @var Builder $vehicleEvents */
        $vehicleEvents = FreeDay::query()
            ->forBranch()
            ->forPeriod($dateFrom->copy(), $dateTo->copy())
            ->where('type', 'busy');

        if ($request->filled('vehicle_id')) {

            $orders->hasWorker($request->input('vehicle_id'));
            $leads->hasWorker($request->input('vehicle_id'));

            $tasks->whereHas('vehicles', function ($q) use ($request) {
                $q->where('machineries.id', $request->input('vehicle_id'));
            });
            $vehicleEvents->forMachine($request->input('vehicle_id'));

        }
        switch ($request->input('filter')) {

            case 'events':
                $tasks = $tasks->get();
                // $vehicleEvents = $vehicleEvents->get();
                break;

            case 'important_events':
                $tasks = $tasks->whereImportant(true)->get();
                //$vehicleEvents = $vehicleEvents->get();
                break;
            case 'orders':
                $orders = $orders->get();
                break;
            case 'leads':
                $leads = $leads->get();
                $preLeads = $preLeads->get();
                break;
            case 'service':
                $vehicleEvents = $vehicleEvents->get();
                break;
            default:
                $leads = $leads->get();
                $preLeads = $preLeads->get();

                $orders = $orders->get();

                $tasks = $tasks->get();
                $vehicleEvents = $vehicleEvents->get();
        }

        $events = $events
            ->merge(LeadEvents::collection($leads instanceof Builder ? collect() : $leads))
            ->merge(OrderEvents::collection($orders instanceof Builder ? collect() : $orders))
            ->merge(TaskEvents::collection($tasks instanceof Builder ? collect() : $tasks))
            ->merge(PreLeadEvents::collection($preLeads instanceof Builder ? collect() : $preLeads))
            ->merge(MachineryEvent::collection($vehicleEvents instanceof Builder ? collect() : $vehicleEvents));

        $events = $events->map(function ($item, $key) {
            $item['ev_id'] = "ev-{$key}";

            return $item;
        });
        return $events;
    }


    function getEventsCountByDay(Request $request)
    {
        $events = collect();

        $dateFrom = Carbon::parse($request->input('date_from'))->setTimezone(config('app.timezone'));
        $dateTo = Carbon::parse($request->input('date_to'))->setTimezone(config('app.timezone'));

        $period = FreeDay::generateDateRange($dateFrom->startOfDay(), $dateTo->endOfDay());

        /** @var Carbon $currentDate */
        foreach ($period as $currentDate) {
            $currentDate->setTimezone(config('app.timezone'));

            $start = $currentDate->copy()->startOfDay();
            $end = $currentDate->copy()->endOfDay();

            /** @var Builder $tasks */
            $tasks = EmployeeTask::query()->forBranch();

            $tasks = $tasks->where(function (Builder $q) use ($dateFrom, $dateTo) {
                return $q->where('employee_id', \Auth::id())
                    ->orWhere('responsible_id', \Auth::id());
            });

            if ($request->filled('status_id')) {
                $tasks = $tasks->where('status', $request->status_id);
            }

            if ($request->filled('customer_id')) {
                $tasks = $tasks->whereHas('customers', function ($q) use ($request) {
                    return $q->where('id', $request->customer_id);
                });
            }

            if ($request->filled('order_id')) {
                $tasks = $tasks->whereHas('orders', function ($q) use ($request) {
                    return $q->where('id', $request->order_id);
                });
            }

            $tasks = $tasks->forPeriod($start->copy(), $end->copy());

            /** @var Builder $orders */
            $orders = Order::query()->forPeriod($start->copy(), $end->copy())->forBranch();
            /** @var Builder $leads */
            $leads = Lead::query()->forPeriod($start->copy(), $end->copy())->forBranch();
            /** @var Builder $preLeads */
            $preLeads = PreLead::query()->whereIn('status', [
                PreLead::STATUS_OPEN,
            ])->forPeriod($start->copy(), $end->copy())->forBranch();

            $orders->hasWorker($request->input('vehicle_id'));
            $leads->hasWorker($request->input('vehicle_id'));

            /** @var Builder $vehicleEvents */
            $vehicleEvents = FreeDay::query()
                ->forBranch()
                ->forPeriod($start->copy(), $end->copy())
                ->forMachine($request->input('vehicle_id'))
                ->where('type', 'busy');

            switch ($request->input('filter')) {

                case 'events':
                    $tasks = $tasks->count();
                    // $vehicleEvents = $vehicleEvents->get();
                    break;

                case 'important_events':
                    $tasks = $tasks->whereImportant(true)->count();
                    //$vehicleEvents = $vehicleEvents->get();
                    break;
                case 'orders':
                    $orders = $orders->count();
                    break;
                case 'leads':
                    $leads = $leads->count();
                    $preLeads = $preLeads->get()->count();
                    break;
                case 'service':
                    $vehicleEvents = $vehicleEvents->count();
                    break;
                default:
                    $leads = $leads->count();
                    $preLeads = $preLeads->get()->count();

                    $orders = $orders->count();
                    $tasks = $tasks->count();
                    $vehicleEvents = $vehicleEvents->count();
            }

            $events = $events
                ->push($leads instanceof Builder ? collect() :
                    $this->pushDayEvent('leads', $start, $end, $leads)
                )
                ->push($orders instanceof Builder ? collect()
                    : $this->pushDayEvent('orders', $start, $end, $orders, '#28a745')
                )
                ->push($tasks instanceof Builder ? collect() : $this->pushDayEvent('events', $start, $end, $tasks,
                    '#f37153'))
                ->push($preLeads instanceof Builder ? collect() : $this->pushDayEvent('preLeads', $start, $end,
                    $preLeads, 'black'))
                ->push($vehicleEvents instanceof Builder ? collect() : $this->pushDayEvent('service', $start, $end,
                    $vehicleEvents, '#00a1ff'));

        }
        $events = $events->map(function ($item, $key) {
            $item['ev_id'] = "ev-{$key}";
            $item['id'] = $key;

            return $item;
        });
        return $events;
    }

    private function pushDayEvent($type, $start, $end, $count, $color = 'grey')
    {
        $name = '';
        switch ($type) {
            case 'leads':
                $name = trans('transbaza_calendar.proposals_count', ['count' => $count]);

                break;
            case 'orders':
                $name = trans('transbaza_calendar.orders_count', ['count' => $count]);
                break;
            case 'events':
                $name = trans('transbaza_calendar.tasks_count', ['count' => $count]);
                break;
            case 'preLeads':
                $name = trans('transbaza_calendar.preleads_count', ['count' => $count]);
                break;
            case 'service':
                $name = trans('transbaza_calendar.services_count', ['count' => $count]);
                break;
        }
        return $count ? [
            'color' => $color,
            'title' => $name,
            'event_title' => $name,
            'start' => $start,
            'end' => $end,
            'editable' => false,
            'type' => $type,
        ] : [];
    }

    /**
     * Store a newly created resource in storage.
     * @param  Request  $request
     * @return Response
     * @throws \Exception
     */
    public function store(Request $request, CalendarService $service)
    {
        $request->validate([
            'date_from' => 'required|date',
            'responsible_id' => 'nullable|integer',
            'time_from' => 'required|date_format:H:i',
            'status_id' => 'nullable|integer',
            //'duration' => 'required|date_format:H:i',
            'date_to' => 'required|date|after_or_equal:date_from',
            'time_to' => ($request->filled('date_to') ? 'required' : 'nullable').'|date_format:H:i',
            'important' => 'nullable|boolean',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string|',
            // 'task_type' => 'required|in:' . implode(',', [EmployeeTask::TYPE_CALL, EmployeeTask::TYPE_EMAIL, EmployeeTask::TYPE_MEETING]),
            'binds' => 'nullable|array',
            'binds.*.id' => 'required|integer',
            'binds.*.type' => 'required|in:vehicle,lead,client,order',
        ]);

        $dateFrom = Carbon::parse($request->input('date_from').' '.$request->input('time_from'));

        $dateTo = Carbon::parse($request->input('date_to').' '.$request->input('time_to'));

        $duration = $dateTo->diffInHours($dateFrom);
        DB::beginTransaction();

        $employeeRoles = \Auth::user()->getBranchRoles($this->currentBranch->id)->toArray();
        /** @var EmployeeTask $task */
        $task = EmployeeTask::create([
            'title' => $request->input('title'),
            'description' => $request->input('description'),
            'task_type' => 'task',
            'status' => $request->filled('status_id') ? $request->status_id : EmployeeTask::STATUS_NEW,
            'important' => toBool($request->input('important')),
            'date_from' => $dateFrom,
            'duration' => $duration ?: 1,
            'date_to' => $duration ? $dateTo : $dateTo->addHour(),
            'creator_id' => \Auth::id(),
            'employee_id' => (in_array(CompanyRoles::ROLE_HEAD, $employeeRoles) || in_array(CompanyRoles::ROLE_ADMIN,
                    $employeeRoles))
                ? $request->input('employee_id', \Auth::id())
                : \Auth::id(),
            'company_branch_id' => $this->currentBranch->id,
            'responsible_id' => $request->responsible_id ?? null
        ]);

        EmployeeTaskOperation::query()->create([
            'employee_task_id' => $task->id,
            'old_status' => null,
            'new_status' => $task->status,
            'created_by_id' => \Auth::id(),
        ]);

        foreach ($request->input('binds') as $bind) {
            switch ($bind['type']) {
                case 'vehicle':

                    if (!empty($bind['isBusy']) && toBool($bind['isBusy'])) {
                        $task->pushVehicleEvent($bind['id']);
                    } else {
                        $task->vehicleCalendar()->delete();
                    }

                    $task->vehicles()->sync($bind['id']);

                    break;
                case 'lead':
                    $task->leads()->sync($bind['id']);
                    break;
                case 'client':
                    $task->customers()->sync($bind['id']);
                    break;
                case 'order':
                    $task->orders()->sync($bind['id']);
                    break;
            }
        }
        $service->createEvent(
            type: GoogleCalendar::TYPE_RENT,
            model: $task,
            summary: $task->title,
            dateFrom: Carbon::parse($task->date_from),
            dateTo: Carbon::parse($task->date_to),
            description: $task->description,
            responsible: $task->responsible?->name,
            employee: $task->employee->name
        );
        DB::commit();


        return response()->json($task);

    }


    /**
     * Update the specified resource in storage.
     * @param  Request  $request
     * @param  int  $id
     * @return Response
     */
    public function update(Request $request, $id, CalendarService $service)
    {
        $request->validate([
            'responsible_id' => 'nullable|integer',
            'date_from' => 'required|date',
            'status_id' => 'nullable|integer',
            'time_from' => 'required|date_format:H:i',
            'date_to' => 'required|date',
            'time_to' => 'required|date_format:H:i',
            // 'duration' => 'required|date_format:H:i',

            'important' => 'nullable|boolean',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string|',
            'binds' => 'nullable|array',
            //'task_type' => 'required|in:' . implode(',', [EmployeeTask::TYPE_CALL, EmployeeTask::TYPE_EMAIL, EmployeeTask::TYPE_MEETING]),

            'binds.*.id' => 'required|integer',
            'binds.*.type' => 'required|in:vehicle,lead,client,order',
        ]);

        $dateFrom = Carbon::parse($request->input('date_from').' '.$request->input('time_from'));

        $dateTo = Carbon::parse($request->input('date_to').' '.$request->input('time_to'));

        $duration = $dateTo->diffInHours($dateFrom);

        $employeeRoles = \Auth::user()->getBranchRoles($this->currentBranch->id)->toArray();
        DB::beginTransaction();

        $task = EmployeeTask::query()->forBranch()->findOrFail($id);
        $oldStatus = $task->status;
        /** @var EmployeeTask $task */
        $task->update([
            'title' => $request->input('title'),
            'description' => $request->input('description'),
            'status' => $request->filled('status_id') ? $request->status_id : $task->status,
            'important' => toBool($request->input('important')),
            'date_from' => $dateFrom,
            'duration' => $duration ?: 1,
            'date_to' => $duration ? $dateTo : $dateTo->addHour(),
            'task_type' => 'task',
            'employee_id' => ((in_array(CompanyRoles::ROLE_HEAD, $employeeRoles) || in_array(CompanyRoles::ROLE_ADMIN,
                    $employeeRoles))
                ? $request->input('employee_id', \Auth::id())
                : \Auth::id()),
            'updated_by_id' => \Auth::id(),
            'responsible_id' => $request->responsible_id ?? null
        ]);

        EmployeeTaskOperation::query()->create([
            'employee_task_id' => $task->id,
            'old_status' => $oldStatus,
            'new_status' => $task->status,
            'created_by_id' => \Auth::id(),
        ]);

        $task->detachAllBinds();

        foreach ($request->input('binds') as $bind) {
            switch ($bind['type']) {
                case 'vehicle':
                    $task->vehicles()->sync($bind['id']);

                    if (!empty($bind['isBusy']) && toBool($bind['isBusy'])) {
                        $task->pushVehicleEvent($bind['id']);
                    }
                    break;
                case 'lead':
                    $task->leads()->sync($bind['id']);
                    break;
                case 'client':
                    $task->customers()->sync($bind['id']);
                    break;
                case 'order':
                    $task->orders()->sync($bind['id']);
                    break;
            }
        }

        $service->createEvent(
            type: GoogleCalendar::TYPE_RENT,
            model: $task,
            summary: $task->title,
            dateFrom: Carbon::parse($task->date_from),
            dateTo: Carbon::parse($task->date_to),
            description: $task->description,
            responsible: $task->responsible?->name,
            employee: $task->employee->name
        );
        DB::commit();


        return response()->json($task);
    }

    /**
     * Remove the specified resource from storage.
     * @param  int  $id
     * @return Response
     */
    public function destroy($id)
    {
        //
    }
}
