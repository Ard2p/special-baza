<?php

namespace Modules\Orders\Entities;

use App\Overrides\Model;
use Modules\ContractorOffice\Entities\Vehicle\Price;

class OrderComponentActual extends Model
{

    protected $table = 'order_components_actual';

    protected $fillable = [
        'order_component_id',
        'amount',
        'auto',
        'cost_per_unit',
        'date_from',
        'date_to',
        'delivery_cost',
        'order_type',
        'order_duration',
        'return_delivery',
        'value_added'
    ];

    protected $casts = [
        'auto' => 'boolean'
    ];

    protected $dates =[
        'date_from',
        'date_to',
    ];

   protected $with = ['services'];

    protected $appends = ['total_sum',
                          'amount',
        'cost_per_unit_without_vat', 'delivery_cost_without_vat',
        'return_delivery_without_vat',
        'value_added_without_vat',
        'amount_without_vat',
        'total_sum_without_vat',
        'insurance_cost',
                          'services_sum', 'services_sum_value_added'
    ];
    private $subContractorCalculation = false;

    function setOrderDurationAttribute($val)
    {
        $this->attributes['order_duration'] = numberToPenny($val);
    }

    function getOrderDurationAttribute($val)
    {
        return $val / 100;
    }

    function orderComponent()
    {
        return $this->belongsTo(OrderComponent::class, 'order_component_id');
    }


    function services()
    {
        return $this->hasMany(OrderComponentServiceActual::class, 'order_component_actual_id');
    }


    function reports()
    {
        return $this->hasMany(OrderComponentReportTimestamp::class, 'order_component_actual_id');
    }

    function getContractorRequisite()
    {
        if($this->contractorRequisite)
            return $this->contractorRequisite;

        /** @var Order $order */
        $order = Order::query()->setEagerLoads([])->whereHas('components', function ($q) {
            $q->where('order_workers.id', $this->order_component_id);

        })->first();

        $this->contractorRequisite = $order->contractorRequisite;
        $this->company_branch = $order->company_branch;

        return  $this->contractorRequisite;
    }

    function getAmountAttribute($val)
    {
        return ($this->cost_per_unit + $this->value_added) * $this->order_duration;
    }

    function getCostPerUnitWithoutVatAttribute()
    {
        return Price::removeVat($this->cost_per_unit, ($this->getContractorRequisite() && $this->contractorRequisite->vat_system === Price::TYPE_CASHLESS_VAT ? $this->company_branch->domain->vat : 0));
    }

    function getDeliveryCostWithoutVatAttribute()
    {
        return Price::removeVat($this->delivery_cost, ($this->getContractorRequisite() && $this->contractorRequisite->vat_system === Price::TYPE_CASHLESS_VAT ? $this->company_branch->domain->vat : 0));
    }

    function getReturnDeliveryWithoutVatAttribute()
    {
        return Price::removeVat($this->return_delivery, ($this->getContractorRequisite() && $this->contractorRequisite->vat_system === Price::TYPE_CASHLESS_VAT ? $this->company_branch->domain->vat : 0));
    }

    function getValueAddedWithoutVatAttribute()
    {
        return Price::removeVat($this->value_added, ($this->getContractorRequisite() && $this->contractorRequisite->vat_system === Price::TYPE_CASHLESS_VAT ? $this->company_branch->domain->vat : 0));
    }

    function getAmountWithoutVatAttribute()
    {
        return Price::removeVat($this->amount, ($this->getContractorRequisite() && $this->contractorRequisite->vat_system === Price::TYPE_CASHLESS_VAT ? $this->company_branch->domain->vat : 0));
    }

    function getTotalSumAttribute()
    {
        return $this->amount + $this->delivery_cost + $this->return_delivery;
    }

    function getTotalSumWithoutVatAttribute()
    {
        return Price::removeVat($this->total_sum, ($this->getContractorRequisite() && $this->contractorRequisite->vat_system === Price::TYPE_CASHLESS_VAT ? $this->company_branch->domain->vat : 0));
    }

    function getInsuranceCostAttribute()
    {
        return 0;
    }

    function getDescriptionAttribute()
    {
        return $this->orderComponent?->description;
    }

    function getTotalSumWithServicesAttribute()
    {
        return $this->total_sum + $this->services_sum;
    }

    function getAmountWithServicesAttribute()
    {
        return $this->amount + $this->services_sum;
    }

    function getServicesSumAttribute()
    {
        return $this->services->sum(function ($service) {
            return $service->count * ($service->price + ($this->subContractorCalculation ? 0 : $service->value_added));
        });
    }

    function getServicesSumValueAddedAttribute()
    {
        return $this->services->sum(function ($service) {
            return $service->count * $service->value_added;
        });
    }

    /**
     * @param  bool  $subContractorCalculation
     */
    public function setSubContractorCalculation(bool $subContractorCalculation): void
    {
        $this->subContractorCalculation = $subContractorCalculation;
    }

}
