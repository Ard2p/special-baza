<?php

namespace Modules\ContractorOffice\Transformers;

use Illuminate\Http\Resources\Json\JsonResource;

class MachinerySetResource extends JsonResource
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
            "company_branch_id" => $this->company_branch_id,
            "equipments" => MachinerySetEquipmentResource::collection($this->equipments),
            "name" => $this->name,
            "prices" => $this->prices,
            "created_at" => $this->created_at,
            "updated_at" => $this->updated_at,
        ];
    }
}
