<?php

namespace Modules\ContractorOffice\Transformers;

use Illuminate\Http\Resources\Json\JsonResource;

class MachinerySetEquipmentPartResource extends JsonResource
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
            "count" => $this->count,
            "created_at" => $this->created_at,
            "id" => $this->id,
            "machinery_set_equipment_id" => $this->machinery_set_equipment_id,
            "part" => $this->part,
            "part_name" => $this->part_name,
            "part_id" => $this->part_id,
            "updated_at" => $this->updated_at,
        ];
    }
}
