<?php

namespace Modules\Integrations\Http\Controllers;

use App\Machinery;
use App\Machines\FreeDay;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class CalendarController extends Controller
{

    public function __construct(Request $request)
    {
        request()->merge(
            [
                'event-start-date' => \request()->input('date_from'),
                'event-end-date' => \request()->input('date_to'),
            ]
        );
    }


    static function eventRules(Request $request)
    {

        return [
            'event-start-date' => 'required|date|date_format:Y-m-d H:i|after:' . Carbon::now()->subDay()->format('Y-m-d') . '|before:' . Carbon::now()->addDays(90)->format('Y-m-d'),
            'event-end-date' => 'required|date|date_format:Y-m-d H:i|after:' . Carbon::now()->subDay()->format('Y-m-d') . '|before:' . Carbon::now()->addDays(90)->format('Y-m-d'),
        ];
    }

    function mapCalendar(FreeDay $calendar)
    {
        return [
            'id' => $calendar->id,
            'date_from' => (string) $calendar->start,
            'date_to' => (string) $calendar->end,
            'vehicle_id' => $calendar->machine_id,
            'type' => $calendar->type
        ];
    }

    function getEvents($machine_id, $event_id = null)
    {
        $machine = Machinery::whereHas('user', function ($q) {
            $q->currentIntegration();

        })->with(['freeDays' => function ($q) use ($event_id){
            if($event_id){
                $q->whereId($event_id);
            }
        }])->findOrFail($machine_id);



        return $event_id
            ? $this->mapCalendar($machine->freeDays->find($event_id))
            : $machine->freeDays->map(function ($calendar){
            return $this->mapCalendar($calendar);
        });
    }

    function addEvent(Request $request, $machine_id)
    {


        $errors = Validator::make($request->all(), self::eventRules($request), FreeDay::getRuleMessages())
            ->errors()
            ->getMessages();

        if ($errors) return response()->json($errors, 419);

        $machine = Machinery::whereHas('user', function ($q) {
            $q->currentIntegration();

        })->with('freeDays')->where('id', $machine_id)->firstOrFail();

        $push = FreeDay::pushEvent($machine, $request);

        if($push instanceof JsonResponse){
            return $push;
        }

        $machine->load('freeDays');

        return $machine->freeDays->map(function ($calendar){
           return $this->mapCalendar($calendar);
        });

    }


    function changeEvent(Request $request, $machine_id, $event_id)
    {
        $errors = Validator::make($request->all(),self::eventRules($request), FreeDay::getRuleMessages())
            ->errors()
            ->getMessages();

        if ($errors) return response()->json($errors, 419);

        $machine = Machinery::whereHas('user', function ($q) {
            $q->currentIntegration();

        })->with('freeDays')->where('id', $machine_id)->firstOrFail();

        $event = $machine->freeDays()->whereType('busy')->findOrFail($event_id);
        $start = Carbon::parse($request->input('event-start-date'));
        $end = Carbon::parse($request->input('event-end-date'));

        if ($start->gt($end)) {
            $c = clone $start;
            $start = clone $end;
            $end = $c;
        }
        if (!FreeDay::checkBusyPeriod($machine->freeDays, $start, $end)) {

            return response()->json(
                [
                    'date_from' => [ trans('transbaza_calendar.period_has_order')],

                ], 400);
        }
        DB::beginTransaction();
        $machine->freeDays()->whereNotIn('id', [$event->id])->whereType('busy')
            ->where(function ($q) use ($start, $end) {
                $q->whereBetween('startDate', [$start, $end])
                    ->orWhere(function ($q) use ($start, $end) {
                        $q->whereBetween('endDate', [$start, $end]);
                    });
            })
            ->delete();
        $event->update([
            'startDate' => $start,
            'endDate' => $end,
        ]);

        DB::commit();

        $machine->load('freeDays');

        return $machine->freeDays->map(function ($calendar){
            return $this->mapCalendar($calendar);
        });
    }

    function deleteEvent($machine_id, $event_id)
    {
        $machine = Machinery::whereHas('user', function ($q) {
            $q->currentIntegration();

        })->with('freeDays')->where('id', $machine_id)->firstOrFail();

        $event = $machine->freeDays()->whereType('busy')->findOrFail($event_id);

        $event->delete();

        $machine->load('freeDays');

        return $machine->freeDays->map(function ($calendar){
            return $this->mapCalendar($calendar);
        });
    }


    function setFree(Request $request, $machine_id)
    {
        $machine = Machinery::whereHas('user', function ($q) {
            $q->currentIntegration();

        })->with('freeDays')->where('id', $machine_id)->firstOrFail();

        $errors = Validator::make($request->all(), FreeDay::eventRules(), FreeDay::getRuleMessages())
            ->errors()
            ->getMessages();

        if ($errors) return response()->json($errors, 419);

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
                    'date_from' => [ trans('transbaza_calendar.period_has_order')],

                ], 400);
        }

        $machine->setFree($start, $end);

        $machine->load('freeDays');

        return $machine->freeDays->map(function ($calendar){
            return $this->mapCalendar($calendar);
        });
    }
}
