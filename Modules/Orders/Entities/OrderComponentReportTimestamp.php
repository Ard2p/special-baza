<?php

namespace Modules\Orders\Entities;

use App\Overrides\Model;

class OrderComponentReportTimestamp extends Model
{

    protected $table = 'order_worker_report_timestamp';

    public $timestamps = false;

    protected $fillable = [
        'date',
        'time_from',
        'time_to',
        'duration',
        'idle_duration',
        'cost_per_unit',
        'order_worker_report_id',
        'order_component_actual_id',
    ];

    protected $casts = [
        'date'  => 'date:Y-m-d',
        'idle_duration'  => 'float',
        'duration'  => 'float',
    ];


    function orderWorkerReport()
    {
        return $this->belongsTo(OrderComponentReport::class, 'order_worker_report_id');
    }

    function orderComponentActual()
    {
        return $this->belongsTo(OrderComponentActual::class, 'order_component_actual_id');
    }
}
