<?php

namespace Modules\Orders\Entities;

use App\Overrides\Model;

class OrderComponentHistory extends Model
{
    protected $fillable = [
        'type',
        'description',
        'order_worker_id',
    ];


    function orderComponent()
    {
        return $this->belongsTo(OrderComponent::class, 'order_worker_id');
    }
}
