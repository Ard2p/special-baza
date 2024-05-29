<?php

namespace Modules\PartsWarehouse\Transformers;

use Illuminate\Http\Resources\Json\JsonResource;
class StockItemCard extends JsonResource
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
            'name' => $this->pivot->name ?: $this->name,
            'original_name' => $this->name,
            'vendor_code' =>  $this->pivot->vendor_code ?: $this->vendor_code,
            'group' => $this->group,
            'amount' => $this->getSameCountForBranch(),
            'stock_items' => $this->stockItems,
            'company_branch_id' => $this->pivot->company_branch_id,
            'part_id' => $this->pivot->part_id,
            'min_order' => $this->pivot->min_order,
            'min_order_type' => $this->pivot->min_order_type,
            'change_hour' => $this->pivot->change_hour,
            'currency' => $this->pivot->currency,
            'tariff_type' => $this->pivot->tariff_type,
            'is_rented' => boolval($this->pivot->is_rented),
            'prices' => $this->pivot->prices,
            'is_for_sale' => $this->pivot->is_for_sale,
            'default_sale_cost' => $this->pivot->default_sale_cost,
            'default_sale_cost_cashless' => $this->pivot->default_sale_cost_cashless,
            'default_sale_cost_cashless_vat' => $this->pivot->default_sale_cost_cashless_vat,
            'models' => $this->models,
        ];
    }
}
