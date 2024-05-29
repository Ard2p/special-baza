<?php

namespace App\Http\Controllers\Machinery;

use App\Machinery;
use App\Machines\FreeDay;
use Carbon\Carbon;
use Carbon\CarbonInterval;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class CalendarController extends Controller
{
    public $format = 'Y/m/d H:i';


    function __construct()
    {
        request()->merge(
            [
                'event-start-date' => str_replace('/', '-', request()->input('event-start-date')),
                'event-end-date' => str_replace('/', '-', request()->input('event-end-date')),
            ]
        );
    }

    public $eventFields = [
        'event-start-date' => 'required|date',
        'event-end-date' => 'required|date',
    ];

    function getEvenFields()
    {
        return [
            'event-start-date' => 'required|date|after:' . Carbon::now()->subDay(1)->format('Y-m-d') . '|before:' . Carbon::now()->addDays(90)->format('Y-m-d'),
            'event-end-date' => 'required|date|after:' . Carbon::now()->subDay(1)->format('Y-m-d') . '|before:' . Carbon::now()->addDays(90)->format('Y-m-d'),
        ];
    }


    function checkHasOrder()
    {

    }

    function setWorkHours(Request $request)
    {
        DB::beginTransaction();
        foreach ($request->hours as $hour) {
            $machine = Machinery::currentUserOrRegional()->findOrFail($hour['machine_id']);
            $from = Carbon::parse($hour['from']);
            $to = Carbon::parse($hour['to']);
            if ($from->gt($to)) {
                $a = (clone $to);
                $to = $from;
                $from = $a;

            }
            $machine->work_hours()->whereDayName($hour['day_name'])->update([
                'from' => $from->format('H:i'),
                'to' => $to->format('H:i'),
            ]);
        }
        DB::commit();
        $machine->refresh();
        return $machine->work_hours;
    }


    function getEvents($id)
    {
        return Machinery::findOrFail($id)->freeDays;
    }


    function changeEvent(Request $request)
    {

        $machine = Machinery::currentUserOrRegional()->findOrFail($request->machine_id);

        return FreeDay::changeEvent($request, $machine);
    }

    function pushEvent(Request $request)
    {
        $errors = Validator::make($request->all(), FreeDay::eventRules(), FreeDay::getRuleMessages())
            ->errors()
            ->getMessages();

        if ($errors) return response()->json($errors, 419);

        $machine = Machinery::currentUserOrRegional()->with('freeDays')->where('id', $request->input('id'))->firstOrFail();

        FreeDay::pushEvent($machine, $request);
    }

    function deleteEvent(Request $request)
    {
        $id = $request->input('id');

        $day = FreeDay::checkFree()->findOrFail($id);

        if ($day->machine->user_id == Auth::id() || Auth::user()->checkRole('admin') || $day->machine->regional_representative_id === Auth::id()) {
            $day->delete();
            return response()->json(['message' => trans('transbaza_calendar.success')]);
        }
        return response()->json(['message' => trans('transbaza_calendar.error')], 400);
    }

    function setFree(Request $request)
    {
        $errors = Validator::make($request->all(), $this->eventFields)
            ->errors()
            ->all();

        if ($errors) return response()->json($errors, 419);


        $machine = Machinery::currentUserOrRegional()->where('id', $request->input('id'))->firstOrFail();

        return FreeDay::setFree($request, $machine);
    }

}
