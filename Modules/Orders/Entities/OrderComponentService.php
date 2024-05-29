<?php

namespace Modules\Orders\Entities;

use App\Overrides\Model;
use Modules\ContractorOffice\Entities\Services\CustomService;
use Modules\Dispatcher\Entities\InvoiceItem;

class OrderComponentService extends Model
{

    protected $table = 'order_worker_services';
    public $timestamps = false;
    private $subContractorCalculation = false;

    protected $fillable = [
        'order_component_id',
        'price',
        'count',
        'value_added',
        'custom_service_id',
        'name',
    ];

    protected $casts = ['value_added' => 'int'];

    protected $appends = ['has_invoice_position'];

    function customService()
    {
        return $this->belongsTo(CustomService::class, 'custom_service_id');
    }
    function orderComponent()
    {
        return $this->belongsTo(OrderComponent::class);
    }

    function invoicePosition()
    {
        return $this->morphOne(InvoiceItem::class, 'owner');
    }

    function getHasInvoicePositionAttribute()
    {
        return $this->invoicePosition()->exists();
    }

    function getPriceDocAttribute()
    {
        return $this->price + ($this->subContractorCalculation ? 0 :$this->value_added);
    }

    /**
     * @param bool $subContractorCalculation
     */
    public function setSubContractorCalculation(bool $subContractorCalculation): void
    {
        $this->subContractorCalculation = $subContractorCalculation;
    }

    function getVendorCodeAttribute()
    {
        return $this->customService?->vendor_code;
    }


}
