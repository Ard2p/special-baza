<?php

namespace Modules\Dispatcher\Entities;

use App\Overrides\Model;

class DispatcherInvoiceDepositTransfer extends Model
{
    protected $fillable = [
        'donor_invoice_id',
        'current_invoice_id',
        'sum',
    ];


    function donorInvoice()
    {
        return $this->belongsTo(DispatcherInvoice::class, 'donor_invoice_id');
    }

    function currentInvoice()
    {
        return $this->belongsTo(DispatcherInvoice::class, 'current_invoice_id');
    }
}
