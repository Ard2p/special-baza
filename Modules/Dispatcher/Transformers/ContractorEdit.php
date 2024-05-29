<?php

namespace Modules\Dispatcher\Transformers;

use Illuminate\Http\Resources\Json\JsonResource;

class ContractorEdit extends JsonResource
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
            'company_name' => $this->company_name,
            'contacts' => $this->contacts,
            'address' => $this->address,
            'contact_person' => $this->contact_person,
            'phone' => $this->phone,
            'region_id' => $this->region_id,
            'domain' => $this->domain,
            'type' => $this->individual_requisites ? $this->individual_requisites->type : 'legal',
            'requisite' => $this->individual_requisites ?: $this->legalRequisites,
            'entity_requisites' => $this->entity_requisites,
            'individual_requisites' => $this->individual_requisites,
            'city_id' => $this->city_id,
            'contract' => $this->contract,
            'vehicles' => $this->vehicles,
        ];
    }
}
