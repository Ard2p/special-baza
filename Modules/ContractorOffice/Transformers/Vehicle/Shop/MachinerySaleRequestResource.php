<?php

namespace Modules\ContractorOffice\Transformers\Vehicle\Shop;

use Illuminate\Http\Resources\Json\JsonResource;

class MachinerySaleRequestResource extends JsonResource
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
            'date' => (string)$this->date,
            'created_at' => (string)$this->created_at,
            'customer_id' => $this->customer_id,
            'phone' => $this->phone,
            'currency' => $this->currency,
            'pay_type' => $this->pay_type,
            'email' => $this->email,
            'contact_person' => $this->contact_person,
            'status' => $this->status,
            'reject_type' => $this->reject_type,
            'creator_id' => $this->creator_id,
            'company_branch_id' => $this->company_branch_id,
            'positions' => $this->positions,
            'customer' => $this->customer,
            'manager' => $this->manager,
            'sales' => $this->sales,
            'status_lng' => $this->status_lng,
            'available_machineries' => $this->getAvailableMachineries(),

        ];
    }
}
