<?php

namespace Modules\Orders\Entities;

use App\Machinery;
use App\Overrides\Model;
use Modules\ContractorOffice\Entities\Sets\MachinerySet;

class MachinerySetsOrder extends Model
{
    protected $fillable = [
        'order_id',
        'machinery_set_id',
        'count',
        'prices'
    ];

    protected $casts =[
        'prices' => 'object'
    ];

    function order()
    {
        return $this->belongsTo(Order::class);
    }

    function machinerySet()
    {
        return $this->belongsTo(MachinerySet::class);
    }

    function orderComponents()
    {
        return $this->hasMany(OrderComponent::class);
    }

    function getNameAttribute()
    {
        return $this->machinerySet->name ?? null;
    }
}
