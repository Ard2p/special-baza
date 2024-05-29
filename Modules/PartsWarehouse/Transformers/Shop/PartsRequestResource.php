<?php

namespace Modules\PartsWarehouse\Transformers\Shop;

use Illuminate\Http\Resources\Json\JsonResource;

class PartsRequestResource extends JsonResource
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
            'date' => (string) $this->date,
            'created_at' => (string) $this->created_at,
            'customer_id' => $this->customer_id,
            'phone' => $this->phone,
            'pay_type' => $this->pay_type,
            'email' => $this->email,
            'contact_person' => $this->contact_person,
            'status' => $this->status,
            'sales' => $this->sales,
            'reject_type' => $this->reject_type,
            'user_id' => $this->user_id,
            'company_branch_id' => $this->company_branch_id,
            'positions' => $this->positions,
            'customer' => $this->customer,
            'manager' => $this->manager,
            'status_lng' => $this->status_lng,
            'orders' => [],
            'available_parts' => $this->getAvailableParts()
        ];
    }
}
