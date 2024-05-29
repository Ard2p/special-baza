<?php

namespace Modules\Orders\Entities\Payments;

use App\Overrides\Model;

class InvoiceRequisite extends Model
{
    protected $fillable = [
        'name',
        'inn',
        'kpp',
        'type',
        'address',
        'invoice_id'
    ];

    function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }
}
