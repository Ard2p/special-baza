<?php

namespace Modules\ContractorOffice\Http\Controllers;

use App\Machinery;
use App\Machines\FreeDay;
use App\Service\RequestBranch;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Modules\CompanyOffice\Services\CompanyRoles;
use Modules\ContractorOffice\Services\VehicleService;
use Modules\ContractorOffice\Transformers\MachineryEvent;
use Modules\ContractorOffice\Transformers\MachineryLeadEvent;
use Modules\Dispatcher\Entities\Lead;
use Modules\Dispatcher\Entities\LeadPosition;
use Modules\Orders\Entities\Order;
use Modules\Orders\Entities\OrderComponent;

class CalendarController extends Controller
{

    private $companyBranch;

    public function __construct(Request $request, RequestBranch $companyBranch)
    {
        $this->companyBranch = $companyBranch->companyBranch;
        $request->merge(
            [
                'event-start-date' => \request()->input('date_from'),
                'event-end-date' => \request()->input('date_to'),
            ]
        );

        $block = $this->companyBranch->getBlockName(CompanyRoles::BRANCH_CALENDAR);

        $this->middleware("accessCheck:{$block},".CompanyRoles::ACTION_SHOW)->only('getEvents', 'getCalendarEvents');
        $this->middleware("accessCheck:{$block},".CompanyRoles::ACTION_CREATE)->only(['addEvent', 'changeEvent']);
        $this->middleware("accessCheck:{$block},".CompanyRoles::ACTION_DELETE)->only(['setFree', 'deleteEvent']);


    }


    static function eventRules(Request $request)
    {

        return [
            'event-start-date' => 'required|date|date_format:Y-m-d H:i|after:'.Carbon::now()->subDays(30)->format('Y-m-d').'|before:'.Carbon::now()->addDays(90)->format('Y-m-d'),
            'event-end-date' => 'required|date|date_format:Y-m-d H:i|after_or_equal:'.$request->input('event-start-date').'|after:'.Carbon::now()->subDays(30)->format('Y-m-d').'|before:'.Carbon::now()->addDays(90)->format('Y-m-d'),
            'comment' => 'nullable|string|max:500',
        ];
    }

    function mapCalendar(FreeDay $calendar)
    {
        return [
            'id' => $calendar->id,
            'date_from' => (string) $calendar->start,
            'date_to' => (string) $calendar->end,
            'start' => (string) $calendar->startDate,
            'end' => (string) $calendar->endDate,
            'vehicle_id' => $calendar->machine_id,
            'order_id' => $calendar->order_id,
            'type' => $calendar->type,
            'title' => $calendar->title,
            'color' => $calendar->color,
            'manager' => $calendar->manager ? $calendar->manager->id_with_email : null,
        ];
    }

    function getEvents($machine_id, $event_id = null)
    {
        $machine = Machinery::forBranch()->findOrFail($machine_id);

        return $event_id
            ? $this->mapCalendar($machine->freeDays->find($event_id))
            : $machine->freeDays->map(function ($calendar) {
                return $this->mapCalendar($calendar);
            });
    }

    function machineryEvents(Request $request, $id)
    {
        $machinery = Machinery::query()->forBranch()->findOrFail($id);

        /** @var Builder $events */
        $events = FreeDay::query()
            ->with('order')
            ->where('machine_id', $id);

        if ($request->filled('customer_id')  || $request->input('status') === 'order') {
            $events->whereHas('order', function ($q) use ($request) {
                if($request->filled('customer_id')) {
                    $q->where('customer_id', $request->input('customer_id'));

                }
            });
        }

        if ($request->filled('date_from')) {
            $df = Carbon::parse($request->input('date_from'));
            $events->whereBetween('startDate', [$df->startOfDay(), $df->clone()->endOfDay()]);
        }

        if ($request->filled('date_to')) {
            $dt = Carbon::parse($request->input('endDate'));
            $events->whereBetween('startDate', [$dt->startOfDay(), $dt->clone()->endOfDay()]);
        }

        if($request->input('status') === 'service' || request()->boolean('is_plan')) {
            $events->whereHas('technicalWork', function (Builder $techWorkBuilder) {
                if(request()->boolean('is_plan')) {
                    $techWorkBuilder->whereRelation('serviceCenter', 'is_plan', true);
                }
            });
        }

        $events = $events
            ->orderBy('startDate', 'desc')
            ->paginate($request->perPage ?: 15);


        return MachineryEvent::collection($events);
    }

    function getCalendarEvents(Request $request, $machine_id)
    {
        if ($request->filled('pagination')) {
            return $this->machineryEvents($request, $machine_id);
        }
        $request->validate([
            'date_from' => 'required|date',
            'date_to' => 'required|date',
        ]);

        $dateFrom = Carbon::parse($request->input('date_from'));
        $dateTo = Carbon::parse($request->input('date_to'));

        /** @var Machinery $machinery */
        $machinery = Machinery::query()->forBranch()->findOrFail($machine_id);

        $events = VehicleService::getEvents($machinery, $dateFrom, $dateTo, $request->input('filter'));

        return response()->json($events);
    }

    function getCountCalendarEvents(Request $request)
    {
        $request->validate([
            'date_from' => 'required|date',
            'date_to' => 'required|date',
        ]);

        $dateFrom = Carbon::parse($request->input('date_from'));
        $dateTo = Carbon::parse($request->input('date_to'));

        /** @var Builder $machineries */
        $machineries = Machinery::query()->forBranch();

        if ($request->filled('base_id')) {
            $machineries = $machineries->where('base_id', $request->base_id);
        }
        if ($request->filled('types_ids')) {
            $machineries = $machineries->whereIn('type', $request->types_ids);
        }
        $type = null;
        $leadsPositions = LeadPosition::query()
            ->select('*', DB::raw('DATE(date_from) as dt'))
            ->whereHas('lead', function ($q) use (
                $dateFrom,
                $dateTo
            ) {
                $q->forPeriod($dateFrom->startOfDay(), $dateTo->endOfDay());
            });
        $machineries_ids = [];

        if ($request->filled('status')) {
            switch ($request->status) {
                case 'free':
                    $machineries->whereDoesntHave('freeDays', function (Builder $q) use ($dateFrom, $dateTo) {
                        $q->forPeriod($dateFrom->startOfDay(), $dateTo->endOfDay(), false);
                    });
                    $machineries_ids = $machineries->get()->pluck('id')->toArray();
                    break;
                case 'repair':
                    $machineries->whereHas('freeDays', function (Builder $q) use ($dateFrom, $dateTo) {
                        $q->forPeriod($dateFrom->startOfDay(), $dateTo->endOfDay(), false)->whereHas('technicalWork');
                    });
                    $machineries_ids = $machineries->get()->pluck('id')->toArray();
                    $type = 'repair';
                    break;
                case 'order':
                    $machineries->whereHas('freeDays', function (Builder $q) use ($dateTo, $dateFrom) {
                        $q->forPeriod($dateFrom->startOfDay(), $dateTo->endOfDay(), false)->whereHas('order');
                    });
                    $machineries_ids = $machineries->get()->pluck('id')->toArray();
                    $type = 'order';
                    break;
                case 'lead':
                    $leadsPositions->get()->each(function ($position, $key) use ($machineries_ids) {
                        array_merge($machineries_ids, $position->vehicles->pluck('id')->toArray());
                    });
                    $type = 'lead';
                    break;
            }
        } else {
            $machineries_ids = $machineries->get()->pluck('id')->toArray();

            $leadsPositions->get()->each(function ($position, $key) use ($machineries_ids) {
                array_merge($machineries_ids, $position->vehicles->pluck('id')->toArray());
            });
            $type = 'lead';
        }

        $events = VehicleService::getAllEvents($machineries_ids, $dateFrom, $dateTo, $type);

        $days[] = $events->groupBy('dt')->map(function ($day) {

            $machineries_count = 0;
            $leads_count = 0;
            $events_count = 0;
            $service_count = 0;
            $service_machineries_count = 0;
            $machineries_ids = [];
            $machineries = [];

            foreach ($day as $event) {
                if ($event['type'] == 'service') {
                    $service_count++;
                    if (isset($event['resourceId']) && !in_array($event['resourceId'], $machineries_ids)) {
                        $machineries_ids[] = $event['resourceId'];
                        $machineries[] = $event['machinery']['model_name'];
                        $service_machineries_count++;
                    }
                } elseif ($event['type'] != 'lead' && $event['type'] != 'service') {
                    $events_count++;
                    if (isset($event['resourceId']) && !in_array($event['resourceId'], $machineries_ids)) {
                        $machineries_ids[] = $event['resourceId'];
                        $machineries[] = $event['machinery']['model_name'];
                        $machineries_count++;
                    }
                }
                if ($event['type'] == 'lead') {
                    if ($event['machinery']['model_name']) {
                        $machineries[] = $event['machinery']['model_name'];
                        $machineries_count++;
                    }
                    $leads_count++;
                }
            }
            return [
                'machineries' => array_splice($machineries, 0, 2),
                'events_count' => $events_count,
                'leads_count' => $leads_count,
                'machineries_count' => $machineries_count,
                'service_count' => $service_count,
                'service_machineries_count' => $service_machineries_count,
            ];
        });

        return response()->json($days[0]);
    }

    function addEvent(Request $request, $machine_id)
    {

        $request->validate(self::eventRules($request), FreeDay::getRuleMessages());


        $machine = Machinery::forBranch()->findOrFail($machine_id);

        $check = FreeDay::forPeriod(Carbon::parse($request->input('event-start-date')),
            Carbon::parse($request->input('event-end-date')))
            ->where('machine_id', $machine_id)->first();
        if ($check) {
            $error = ValidationException::withMessages([
                'errors' => ['В этом пеериоде есть события. Удалите или измените их прежде чем добавить новое.']
            ]);

            throw $error;
        }


        $push = FreeDay::pushEvent($machine, $request);

        if ($push instanceof JsonResponse) {
            return $push;
        }

        $machine->load('freeDays');

        return $machine->freeDays->map(function ($calendar) {
            return $this->mapCalendar($calendar);
        });

    }


    function changeEvent(Request $request, $machine_id, $event_id)
    {
        $request->validate(self::eventRules($request), FreeDay::getRuleMessages());

        $machine = Machinery::forBranch()->findOrFail($machine_id);

        $event = $machine->freeDays()->whereType('busy')->findOrFail($event_id);
        $start = Carbon::parse($request->input('event-start-date'));
        $end = Carbon::parse($request->input('event-end-date'));

        if ($start->gt($end)) {
            $c = clone $start;
            $start = clone $end;
            $end = $c;
        }
        if (FreeDay::query()->forPeriod($start->copy(), $end->copy())->where('id', '!=', $event_id)->where('machine_id',
            $machine->id)->exists()) {

            return response()->json(
                [
                    'date_from' => [trans('transbaza_calendar.period_has_order')],

                ], 400);
        }
        DB::beginTransaction();
        $machine->freeDays()->whereNotIn('id', [$event->id])->whereType('busy')
            ->where(function ($q) use ($start, $end) {
                $q->whereBetween('startDate', [$start, $end])
                    ->orWhere(function ($q) use ($start, $end) {
                        $q->whereBetween('endDate', [$start, $end]);
                    })
                    ->orWhere(function ($q) use ($start, $end) {
                        $q->where('startDate', '<=', $start)
                            ->where('endDate', '>=', $end);
                    });
            })
            ->delete();
        $event->update([
            'startDate' => $start,
            'endDate' => $end,
        ]);

        DB::commit();

        $machine->load('freeDays');

        return $machine->freeDays->map(function ($calendar) {
            return $this->mapCalendar($calendar);
        });
    }

    function deleteEvent($machine_id, $event_id)
    {
        $machine = Machinery::forBranch()->findOrFail($machine_id);

        $event = $machine->freeDays()->whereIn('type', ['busy', 'day_off'])->findOrFail($event_id);

        $event->delete();

        return response()->json();
    }


    function setFree(Request $request, $machine_id)
    {
        $machine = Machinery::forBranch()->findOrFail($machine_id);

        $errors = Validator::make($request->all(), FreeDay::eventRules(), FreeDay::getRuleMessages())
            ->errors()
            ->getMessages();

        if ($errors) {
            return response()->json($errors, 419);
        }

        $start = Carbon::parse($request->input('event-start-date'));
        $end = Carbon::parse($request->input('event-end-date'))->addSeconds(59);

        if ($start->gt($end)) {
            $c = clone $start;
            $start = clone $end;
            $end = $c;
        }
        if (!FreeDay::checkBusyPeriod($machine->freeDays, $start, $end)) {

            return response()->json(
                [
                    'date_from' => [trans('transbaza_calendar.period_has_order')],

                ], 400);
        }

        $machine->setFree($start, $end);

        $machine->load('freeDays');

        return $machine->freeDays->map(function ($calendar) {
            return $this->mapCalendar($calendar);
        });
    }
}
