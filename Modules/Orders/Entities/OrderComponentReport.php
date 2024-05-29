<?php

namespace Modules\Orders\Entities;

use Carbon\Carbon;
use App\Overrides\Model;

class OrderComponentReport extends Model
{

    protected $table = 'order_worker_reports';

    protected $fillable = [
        'order_worker_id',
        'worker_type',
        'worker_id',
    ];

    protected $with = ['reportTimestamps'];

    protected $appends = ['total_hours', 'date_from', 'date_to'];

    function worker()
    {
        return $this->morphTo();
    }

    function getTotalHoursAttribute()
    {
        return $this->reportTimestamps()->sum('duration');
    }

    function getDateFromAttribute()
    {
        return (string) Carbon::parse($this->reportTimestamps()->min('date'));
    }

    function getDateToAttribute()
    {
        return (string) Carbon::parse($this->reportTimestamps()->max('date'));
    }

    function orderComponent()
    {
        return $this->belongsTo(OrderComponent::class, 'order_worker_id');
    }


    function reportTimestamps()
    {
        return $this->hasMany(OrderComponentReportTimestamp::class, 'order_worker_report_id');
    }
}
