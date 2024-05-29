<?php

namespace App\Machines;

use App\Machinery;
use App\Service\RequestBranch;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use App\Overrides\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Modules\CompanyOffice\Entities\Employees\EmployeeTask;
use Modules\CompanyOffice\Services\HasManager;
use Modules\ContractorOffice\Entities\Vehicle\TechnicalWork;
use Modules\ContractorOffice\Services\VehicleTechnicalWorkService;
use Modules\FMSAPI\Entities\FmsApi;
use Modules\FMSAPI\Entities\FmsRestApi;
use Modules\Orders\Entities\Order;
use Modules\Orders\Entities\OrderComponent;
use Modules\Orders\Services\OrderService;

class FreeDay extends Model
{
    use HasManager;

    public $timestamps = false;

    protected $dateFormat;

    /*   protected $casts = [
           'startDate'  => 'date:Y-m-d H:i:s',
           'endDate' => 'date:Y-m-d H:i:s',
       ];*/
    const FORMAT = 'Y-m-d H:i';

    protected $dates = [
        'startDate',
        'endDate'
    ];
    protected $fillable = [
        'machine_id',
        'startDate',
        'endDate',
        'type',
        'employee_task_id',
        'technical_work_id',
        'order_component_id',
        'order_id',
        'comment',
        'busy_type',
        'creator_id'
    ];

    static function getRuleMessages()
    {
        return [
            'event-start-date.after' => trans('transbaza_calendar.date_need_early'),
            'event-end-date.after' => trans('transbaza_calendar.date_need_later'),
            'event-start-date.before' => trans('transbaza_calendar.date_need_three_month_early'),
            'event-end-date.before' => trans('transbaza_calendar.date_need_three_month_later'),
        ];
    }

    public static $snakeAttributes = false;

    protected $appends = [
        'start',
        'end',
        'color',
        'title',
        'textColor',
        'overlap',
        //'allDay',
        'resourceEditable',
    ];

    protected static function boot()
    {
        parent::boot();

//        static::updated(function ($model) {
//            if (!FmsApi::isCurrentRequest() && $model->hasIntegration() && $model->type === 'order') {
//                $api = new FmsRestApi();
//                $api->updateCalendar($model);
//            }
//
//        });
//
//        static::created(function ($model) {
//            if (!FmsApi::isCurrentRequest() && $model->hasIntegration() && $model->type === 'order') {
//                $api = new FmsRestApi();
//                $api->createCalendar($model);
//            }
//        });
//
//        static::deleted(function ($model) {
//            if (!FmsApi::isCurrentRequest() && $model->hasIntegration() && $model->type === 'order') {
//                $api = new FmsRestApi();
//                $api->deleteCalendar($model);
//            }
//        });


    }

    static function eventRules()
    {
        return [
            'event-start-date' => 'required|date|date_format:Y-m-d H:i|after:' . Carbon::now()->subDay(1)->format('Y-m-d') . '|before:' . Carbon::now()->addDays(90)->format('Y-m-d'),
            'event-end-date' => 'required|date||date_format:Y-m-d H:i|after:' . Carbon::now()->subDay(1)->format('Y-m-d') . '|before:' . Carbon::now()->addDays(90)->format('Y-m-d'),
        ];
    }


    function employeeTask()
    {
        return $this->belongsTo(EmployeeTask::class);
    }

    function orderWorker()
    {
        return $this->belongsTo(OrderComponent::class, 'order_component_id');
    }


    function scopeForMachine(Builder $q, $machineId = null)
    {
        if($machineId) {
            $q->where('machine_id', $machineId);
        }
    }


    function hasIntegration()
    {
        return $this->machine->company_branch->integrations->isNotEmpty();
    }

    function busyType()
    {
        return $this->hasOne(BusyType::class, 'key', 'busy_type');
    }

    function technicalWork()
    {
        return $this->belongsTo(TechnicalWork::class);
    }

    function getEventRules()
    {
        return [
            'event-start-date' => 'required|date|after:' . Carbon::now()->subDay(1)->format('Y-m-d') . '|before:' . Carbon::now()->addDays(90)->format('Y-m-d'),
            'event-end-date' => 'required|date|after:' . Carbon::now()->subDay(1)->format('Y-m-d') . '|before:' . Carbon::now()->addDays(90)->format('Y-m-d'),
        ];
    }

    public function machine()
    {
        return $this->belongsTo(Machinery::class, 'machine_id');
    }

    function order()
    {
        return $this->belongsTo(Order::class);
    }

    function scopeCheckFree($q)
    {
        return $q->where('type', 'free');
    }

    function scopeBusy($q)
    {
        return $q->whereIn('type', ['busy', 'order']);
    }


    static function deleteInside($machine_id, Carbon $minDate, Carbon $maxDate)
    {
        FreeDay::where('machine_id', $machine_id)
            ->whereDate('startDate', '>=', $minDate)
            ->whereDate('startDate', '<=', $maxDate)
            ->whereDate('endDate', '<=', $maxDate)
            ->whereDate('endDate', '>=', $minDate)
            ->where('type', 'busy')
            ->delete();
    }

    function getStartAttribute()
    {
        return (string)$this->startDate->format('c');
    }


    static function pushBusyEvent($start_Date, $end_date, $machine_id)
    {

    }

    static function deleteBadPeriods($period_collection): void
    {
        foreach ($period_collection as $p) {
            if ($p->startDate > $p->endDate && $p->type === 'busy') {
                $p->delete();
            }
        }
    }

    function getEndAttribute()
    {
        /*$dayDiffs = $this->startDate->diffInDays($this->endDate);
        $hourDiffs = $this->startDate->diffInHours($this->endDate);
        if($this->startDate->eq($this->endDate) || ($hourDiffs === 24 * $dayDiffs)){
            $this->endDate =  $this->endDate->addHours(23);
        }*/
        return (string)$this->endDate->format('c');
    }

    function getColorAttribute()
    {
        if ($this->type === 'order') {
            return '#3788d8';
        }
        return 'orange';
    }

    function getAllDayAttribute()
    {
        return (string)$this->startDate === (string)$this->startDate->clone()->startOfDay() && (string)$this->endDate === (string)$this->endDate->clone()->endOfDay();
    }

    function getTextColorAttribute()
    {

        return 'white';
    }

    function getTitleAttribute()
    {
        return ($this->type === 'order') ? "#{$this->order_id}" : ($this->comment ?: ($this->busyType->name ?? trans('contractors/edit.busy')));
    }

    function getOverlapAttribute()
    {
        return false;
    }

    function getResourceEditableAttribute()
    {
        return false;
    }

    static function checkBusyPeriod($collection, $minDate, $maxDate)
    {
        $check1 = $collection->filter(function ($item) use ($minDate, $maxDate) {

            return (data_get($item, 'startDate') >= $minDate)
                && (data_get($item, 'startDate') <= $maxDate)
                && (data_get($item, 'type') == 'order');
        })->first();

        $check2 = $collection->filter(function ($item) use ($minDate, $maxDate) {

            return (data_get($item, 'endDate') <= $maxDate)
                && (data_get($item, 'endDate') >= $minDate)
                && (data_get($item, 'type') == 'order');
        })->first();

        if ($check1 || $check2) {
            return false;
        }

        return true;
    }


    static function changeEvent(Request $request, Machinery $machine)
    {

        $id = $request->id;

        $event = $machine->freeDays()->whereType('busy')->findOrFail($id);
        $start = Carbon::parse($request->start);
        $end = Carbon::parse($request->end);

        if ($start->gt($end)) {
            $c = clone $start;
            $start = clone $end;
            $end = $c;
        }
        if (!self::checkBusyPeriod($machine->freeDays, $start, $end)) {

            return response()->json(
                [
                    'event-start-date' => [trans('transbaza_calendar.period_has_order')],
                    // 'id' => $machine->id
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
        return $event;
    }

    /**
     * @param Machinery $machine
     * @param Request $request
     * @param string $format
     * @return \Illuminate\Http\JsonResponse
     * @throws \Exception
     */
    static function pushEvent(Machinery $machine, $request, $format = self::FORMAT)
    {

        $minDate = Carbon::createFromFormat($format, $request->input('event-start-date'));
        $maxDate = Carbon::createFromFormat($format, $request->input('event-end-date'))->addSeconds(59);
        if ($minDate > $maxDate) {
            $c = $maxDate;
            $maxDate = $minDate;
            $minDate = $c;
        }

        if ($maxDate->format('H:i') === '00:00') {
            $maxDate = $maxDate->equalTo($minDate) ? $maxDate->endOfDay() : $maxDate->subDay(1)->endOfDay();
        }

        $collection = $machine->freeDays;
        if (!FreeDay::checkBusyPeriod($collection, $minDate, $maxDate)) {

            return response()->json(
                [
                    'event-start-date' => [trans('transbaza_calendar.period_has_order')],
                    // 'id' => $machine->id
                ], 400);
        }

        $range = self::generateDateRange(clone $minDate, clone $maxDate);
        $day = 0;

        DB::beginTransaction();

        if($request->input('busy_type') && $request->input('busy_type') !== 'day_off') {
            if($machine->freeDays()->forPeriod($minDate, $maxDate, false)->whereHas('technicalWork')->exists()) {
                return response()->json(
                    [
                        'event-start-date' => ['В периоде уже есть сервис.'],
                        // 'id' => $machine->id
                    ]);
            }
            $service = new VehicleTechnicalWorkService($machine);
            $service->addService($request->input('busy_type'), collect($request->input('mechanics'))->pluck('id')->toArray(), $request->input('engine_hours'));

        }
        foreach ($range as $date) {
            ++$day;
            if ((count($range) !== 1)) {
                $machine->freeDays()->whereDate('startDate', $date->format('Y-m-d'))->whereType('busy')->delete();
            } else {
                $machine->freeDays()->whereType('busy')
                    ->where(function ($q) use ($minDate, $maxDate) {
                        $q->whereBetween('startDate', [$minDate, $maxDate])
                            ->orWhere(function ($q) use ($minDate, $maxDate) {
                                $q->whereBetween('endDate', [$minDate, $maxDate]);
                            });
                    })
                    ->delete();

            }
            $machine->freeDays()->whereType('busy')
                ->where('startDate', '<=', $minDate)
                ->where('endDate', '>=', $maxDate)
                ->delete();
            //   $day_name = $date->format('D');
            //  $work = $machine->work_hours()->whereDayName($day_name)->first();
            if(isset($service)) {
               // $service->addPeriod($day === 1 ? $minDate : $date->startOfDay(), ((count($range) === $day) ? $maxDate : (clone $date)->endOfDay()));
            }else {
                $event = FreeDay::create([
                    /*   'startDate' => ((count($range) !== 1) ? Carbon::parse("{$date->format('Y-m-d')} {$work->from}") : $minDate),
                       'endDate' => ((count($range) !== 1) ? Carbon::parse("{$date->format('Y-m-d')} {$work->to}") : $maxDate),*/
                    'startDate' => $day === 1 ? $minDate : $date->startOfDay(),
                    'endDate' => ((count($range) === $day) ? $maxDate : (clone $date)->endOfDay()),
                    'type' => $request->input('busy_type') === 'day_off' ? 'day_off' : 'busy',
                    'machine_id' => $machine->id,
                    'creator_id' => Auth::check() ? Auth::id() : null,
                    //'busy_type' => $request->busy_type
                ]);
            }
        }
        if(isset($service)) {
            $component = OrderComponent::query()->forPeriod($minDate, $maxDate)->whereWorkerType(Machinery::class)
                ->whereWorkerId($machine->id)->first();

            $event = $service->addPeriod($minDate, $maxDate);
            if($component) {
                $service->currentService->update([
                    'order_component_id' => $component->id
                ]);
                $orderService = new OrderService();
                $orderService->setOrder($component->order)
                    ->setIdle(
                        $component->id,
                        $minDate,
                        $maxDate
                    );
            }
        }
        DB::commit();

        return $event ?? response()->json();
    }


    static function generateDateRange(Carbon $start_date, Carbon $end_date, $startEnd = false)
    {
        $dates = [];
        $end = $end_date->clone();

        if($startEnd){
         $end = $end->endOfDay();
        }


        for ($date = clone $start_date; $date->lte($end); $date->addDay()) {
            $dates[] = clone $date;
        }

        return $dates;
    }

    static function generateMonthRange(Carbon $start_date, Carbon $end_date)
    {
        $dates = [];
        $end = $end_date->clone()->startOfMonth();

        for ($date = clone $start_date->startOfMonth(); $date->lte($end); $date->addMonth()) {
            $dates[] = clone $date;
        }

        return $dates;
    }

    static function setFree($request, $machine, $format = self::FORMAT)
    {
        $minDate = Carbon::createFromFormat($format, $request->input('event-start-date'));
        $maxDate = Carbon::createFromFormat($format, $request->input('event-end-date'));
        if ($minDate > $maxDate) {
            $c = $maxDate;
            $maxDate = $minDate;
            $minDate = $c;
        }


        DB::beginTransaction();
        $collection = $machine->freeDays;
        self::deleteBadPeriods($collection);
        $machine->load('freeDays');

        $machine->setFree($minDate, $maxDate);
        DB::commit();
        return response()->json(['message' => trans('transbaza_calendar.success'), 'id' => $machine->id]);
    }

    function scopeForPeriod($q, Carbon $dateFrom, Carbon $dateTo, $setAllDay = true)
    {
        if($setAllDay) {
            $dateFrom->startOfDay();
            $dateTo->endOfDay();
        }

        return $q->where(function (Builder $q) use ($dateFrom, $dateTo) {
            $q->where(function ($q) use ($dateFrom) {
                $q->where('startDate', '<=', $dateFrom);
                $q->where('endDate', '>=', $dateFrom);

            })->orWhere(function ($q) use ($dateTo) {
                $q->where('startDate', '<=', $dateTo);
                $q->where('endDate', '>=', $dateTo);
            })
            ->orWhereBetween('startDate', [$dateFrom, $dateTo])
            ->orWhereBetween('endDate', [$dateFrom, $dateTo]);

        });

    }

    function scopeForBranch($q, $branch_id = null)
    {
        $branch_id = $branch_id ?: app(RequestBranch::class)->companyBranch->id;
        return $q->whereHas('machine', function ($q) use ($branch_id) {
            $q->forBranch($branch_id);
        });
    }


}
