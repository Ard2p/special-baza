<?php

namespace Modules\Orders\Entities;

use App\Overrides\Model;

class OrderComponentIdle extends Model
{

    const TYPE_CONTRACTOR = 1;
    const TYPE_CUSTOMER = 2;

    public $timestamps = false;
    protected $fillable = [
        'date_from',
        'date_to',
        'order_worker_id',
        'type',
    ];

    protected $casts = [
        'date_from' => 'datetime',
        'date_to' => 'datetime',
    ];


    function orderComponent()
    {
        return $this->belongsTo(OrderComponent::class, 'order_worker_id');
    }
}
