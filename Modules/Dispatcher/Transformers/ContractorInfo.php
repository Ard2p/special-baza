<?php

namespace Modules\Dispatcher\Transformers;

use Illuminate\Http\Resources\Json\JsonResource as Resource;

class ContractorInfo extends Resource
{
    /**
     * Transform the resource into an array.
     *
     * @param \Illuminate\Http\Request
     * @return array
     */
    public function toArray($request)
    {
        return [
            'id'                    => $this->id,
            'company_name'          => $this->company_name,
            'address'               => $this->address,
            'contact_person'        => $this->contact_person,
            'phone'                 => $this->phone,
            'region_id'             => $this->region_Did,
            'city_id'               => $this->city_id,
            'entity_requisites'     => $this->entity_requisites,
            'individual_requisites' => $this->individual_requisites,
            'region'                => $this->region
                ? $this->region->name
                : '',
            'city'                  => $this->city
                ? $this->city->name
                : '',
            'machineries'           => $this->machineries,
            'type'                  => $this->type,
            'contract'              => $this->contract,
            'last_application_id'   => $this->last_application_id,
            'currency'              => $this->domain->currency,
            'orders_sum'            => $this->orderComponents()->accepted()->get()->sum('amount') - $this->orderComponents()->accepted()->get()->sum('value_added')
                ?: 0,
            'orders'                => $this->orderComponents,
            'created_at'            => (string)$this->created_at,
            'debt'                  => $this->getDebt(),
        ];
    }
}
