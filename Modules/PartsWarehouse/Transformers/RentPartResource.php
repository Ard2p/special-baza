<?php

namespace Modules\PartsWarehouse\Transformers;

use Illuminate\Http\Resources\Json\JsonResource;

class RentPartResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request
     * @return array
     */
    public function toArray($request)
    {
        $cost_per_unit = 0;
        $pay_type = $request->input('pay_type');
        if (count($this->part->company_branches->first()->pivot->prices) > 0) {
            $tariff = $this->part->company_branches->first()->pivot->prices()->whereHas('unitCompare',
                function ($p) use ($request) {
                    $p->where('type', $request->input('order_type'));
                })->first();
            if ($request->individual_customer) {
                if ($pay_type == 'cash') {
                    $cost_per_unit = $tariff->gridPrices->where('price_type', 'cash')->first()->price;
                } elseif ($pay_type == 'cashless') {
                    $cost_per_unit = $tariff->gridPrices->where('price_type',
                            'cashless_without_vat')->first()->price;
                }
            } else {
                if ($pay_type == 'cash') {
                    $cost_per_unit = $tariff->gridPrices->where('price_type', 'cash')->first()->price;
                } elseif ($pay_type == 'cashless') {
                    $cost_per_unit = $tariff->gridPrices->where('price_type',
                            'cashless_without_vat')->first()->price;
                }
            }
        }

        return [
            'id' => $this->id,
            'unit' => $this->unit,
            'unit_id' => $this->unit_id,
            'brand' => $this->brand,
            'brand_id' => $this->brand_id,
            'count' => $this->count,
            'name' => $this->name,
            'vendor_code' => $this->part->vendor_code,
            'group' => $this->part->group,
            'part' => $this->part,
            'available_rent_amount' => $this->available_rent_amount,
            'available_amount' => $this->available_amount,
            'amount' => $this->part->getSameCountForBranch(),
            'part_id' => $this->part_id,
            'min_order' => $this->part->company_branches->first()->pivot->min_order,
            'min_order_type' => $this->part->company_branches->first()->pivot->min_order_type,
            'change_hour' => $this->part->company_branches->first()->pivot->change_hour,
            'currency' => $this->part->company_branches->first()->pivot->currency,
            'tariff_type' => $this->part->company_branches->first()->pivot->tariff_type,
            'is_rented' => boolval($this->part->company_branches->first()->pivot->is_rented),
            'cost_per_unit' => (!empty($cost_per_unit)) ? $cost_per_unit / 100 : 0,
            'stock' => $this->stock,
            'stock_id' => $this->stock_id,
            'prices' => PriceReosurce::collection($this->part->company_branches->first()->pivot->prices)
        ];
    }
}
