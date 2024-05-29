<?php

namespace Modules\PartsWarehouse\Transformers;

use Illuminate\Http\Resources\Json\JsonResource;

class PriceReosurce extends JsonResource
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
            "id" => $this->id,
            "unit_compare_id" => $this->unit_compare_id,
            "unit_compare_type" => $this->unitCompare->type,
            "min" => $this->min,
            "max" => $this->max,
            "is_fixed" => $this->is_fixed,
            "market_markup" => $this->market_markup,
            "type" => $this->type,
            "machinery_id" => $this->machinery_id,
            "sort_order" => $this->sort_order,
            "machinery_type" => $this->machinery_type,
            "grid_prices" => $this->gridPrices,
        ];
    }
}
