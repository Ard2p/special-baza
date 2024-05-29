<?php

namespace Modules\Dispatcher\Entities;

use App\Overrides\Model;
use Modules\Orders\Entities\Order;
use Modules\Orders\Entities\OrderComponent;

class ContractorPay extends Model
{

    protected $table = 'dispatcher_contractor_pays';

    protected $fillable = [
        'type',
        'date',
        'sum',
        'order_worker_id',
        'contractor_id',
        'contractor_type'
    ];


    protected $dates = ['date'];


    function contractor()
    {
        return $this->morphTo('contractor');
    }

    function orderWorker()
    {
        return $this->belongsTo(OrderComponent::class, 'order_worker_id');
    }



}
