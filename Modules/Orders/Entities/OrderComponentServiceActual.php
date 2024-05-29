<?php

namespace Modules\Orders\Entities;

use App\Directories\Unit;
use Illuminate\Database\Eloquent\Model;
use Modules\ContractorOffice\Entities\Services\CustomService;
use Modules\Dispatcher\Entities\InvoiceItem;

class OrderComponentServiceActual extends Model
{
    public $timestamps = false;
    private $subContractorCalculation = false;

    protected $fillable = [
        'order_component_actual_id',
        'price',
        'count',
        'value_added',
        'custom_service_id',
        'unit_id',
        'name',
    ];

    protected $casts = ['value_added' => 'int'];

    function customService()
    {
        return $this->belongsTo(CustomService::class, 'custom_service_id');
    }

    function orderComponent()
    {
        return $this->belongsTo(OrderComponentActual::class);
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

    function unit()
    {
        return $this->belongsTo(Unit::class);
    }

}
