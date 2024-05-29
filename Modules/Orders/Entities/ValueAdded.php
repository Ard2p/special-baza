<?php

namespace Modules\Orders\Entities;

use App\User;
use App\Overrides\Model;
use Modules\CompanyOffice\Entities\Company\CompanyBranch;

class ValueAdded extends Model
{

    public $timestamps = false;
    protected $table = 'order_workers_value_added';

    protected $fillable = [
        'amount',
        'order_worker_id',
        'worker_type',
        'worker_id',
        'owner_id'
    ];


    function owner()
    {
        return $this->belongsTo(CompanyBranch::class);
    }

    function worker()
    {
        return $this->morphTo();
    }

    function orderComponent()
    {
        return $this->belongsTo(OrderComponent::class, 'order_worker_id');
    }
}
