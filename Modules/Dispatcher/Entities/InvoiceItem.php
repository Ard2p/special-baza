<?php

namespace Modules\Dispatcher\Entities;

use App\Overrides\Model;

class InvoiceItem extends Model
{
    protected $fillable = [
        'owner_type',
        'owner_id',
        'cost_per_unit',
        'amount',
        'name',
        'description',
        'vendor_code',
        'unit',
        'invoice_id',
        'part_duration',
    ];


    function owner()
    {
        return $this->morphTo();
    }

    function invoice()
    {
        return $this->belongsTo(DispatcherInvoice::class, 'invoice_id');
    }


    function getSumAttribute()
    {
        return $this->amount * $this->cost_per_unit;
    }


}
