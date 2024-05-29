<?php

namespace Modules\Dispatcher\Entities;

use Illuminate\Database\Eloquent\Model;

class DispatcherInvoiceLeadPivot extends Model
{

    protected $table = 'invoice_lead_pivot';
    protected $fillable = [
        'name',
        'order_duration',
        'order_type',
        'vendor_code',
        'cost_per_unit',
        'date_from',
        'delivery_cost',
        'return_delivery',
    ];

    public $timestamps = false;

    protected $dates = [
        'date_from'
    ];

    function invoice()
    {
        return $this->belongsTo(DispatcherInvoice::class, 'invoice_id');
    }


    function getDateToAttribute()
    {
        return getDateTo($this->date_from, $this->order_type, $this->order_duration);
    }
}
