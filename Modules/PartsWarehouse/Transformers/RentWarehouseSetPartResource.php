<?php

namespace Modules\PartsWarehouse\Transformers;

use App\Service\RequestBranch;
use Illuminate\Http\Resources\Json\JsonResource;

class RentWarehouseSetPartResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request
     * @return array
     */
    public function toArray($request)
    {
        $part = $this->company_branches_warehouse_part->part;
        $stock_item = $part->stockItems()->forBranch(app(RequestBranch::class)->companyBranch->id)->first();

        return [
            "id" => $this->id,
            "part_id" => $part->id,
            "stock_id" => $stock_item->stock_id,
            "unit_id" => $part->unit_id,
            "amount" => $this->count,
            "cost_per_unit" => $this->cost_per_unit,
            "owner_type" => $stock_item->owner_type,
            "owner_id" => $stock_item->owner_id,
            "company_branch_id" => 128,
            "placement_name" => "Склад #2",
            "part" => $part,
            "unit" => $part->unit,
            "stock" => $stock_item->stock
        ];
    }
}
