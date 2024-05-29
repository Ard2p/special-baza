<?php

namespace Modules\Dispatcher\Transformers;

use Illuminate\Http\Resources\Json\JsonResource;

class ContractorsList extends JsonResource
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
            'id' => $this->id,
            'company_name' => $this->company_name,
            'address' => $this->address,
            'contact_person' => $this->contact_person,
            'phone' => $this->phone,
            'vehicles_count' => $this->machineries()->count(),
            'orders_count' => $this->orders()->count(),
            'created_at' => (string) $this->created_at,
            'city' => $this->city ? $this->city->name : '',
        ];
    }
}
