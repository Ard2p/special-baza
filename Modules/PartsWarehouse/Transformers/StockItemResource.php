<?php

namespace Modules\PartsWarehouse\Transformers;

use Illuminate\Http\Resources\Json\JsonResource;

class StockItemResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request
     * @return array
     */
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'unit' => $this->unit,
            'brand' => $this->brand,
            'name' =>   $this->pivot->name ?: $this->name,
            'original_name' => $this->name,
            'vendor_code' =>  $this->pivot->vendor_code ?: $this->vendor_code,
            'amount' => $this->getSameCountForBranch(),
            'is_for_sale' => $this->pivot->is_for_sale,
            'is_rented' => $this->pivot->is_rented,
            'models' => $this->models,
            'rented' => $this->getRentedCount(),
            'in_service' => $this->getInServicesCount(),
            'in_sale' => $this->getSalesCount(),
            'default_sale_cost' => $this->pivot->default_sale_cost,
            'default_sale_cost_cashless' => $this->pivot->default_sale_cost_cashless,
            'default_sale_cost_cashless_vat' => $this->pivot->default_sale_cost_cashless_vat,
        ];
    }
}
