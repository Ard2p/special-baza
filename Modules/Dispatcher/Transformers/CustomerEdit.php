<?php

namespace Modules\Dispatcher\Transformers;

use Illuminate\Http\Resources\Json\JsonResource as Resource;

class CustomerEdit extends Resource
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
            'contact_position' => $this->contact_position,
            'phone' => $this->phone,
            'email' => $this->email,
            'domain' => $this->domain,
            'requisite' => $this->individual_requisites ?: $this->legalRequisites,
            'type' => $this->individual_requisites ? $this->individual_requisites->type : 'legal',
            'region_id' => $this->region_id,
            'creator_id' => $this->creator_id,
            'city_id' => $this->city_id,
            'has_requisite' => $this->hasRequisite(),
            'tasks_count' => $this->tasks_count,
            'latest_task' => ($this->tasks->last()) ? $this->tasks->last()->date_from : null,
            'created_at' => (string) $this->created_at,
        ];
    }
}
