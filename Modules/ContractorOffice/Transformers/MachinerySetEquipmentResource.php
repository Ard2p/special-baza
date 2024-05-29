<?php

namespace Modules\ContractorOffice\Transformers;

use Illuminate\Http\Resources\Json\JsonResource;

class MachinerySetEquipmentResource extends JsonResource
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
            "brand_id" => $this->brand_id,
            "category_id" => $this->category_id,
            "count" => $this->count,
            "id" => $this->id,
            "machinery_set_id" => $this->machinery_set_id,
            "model_id" => $this->model_id,
            "parts" => MachinerySetEquipmentPartResource::collection($this->parts),
        ];
    }
}
