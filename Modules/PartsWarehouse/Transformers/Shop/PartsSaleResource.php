<?php

namespace Modules\PartsWarehouse\Transformers\Shop;

use App\User\IndividualRequisite;
use Illuminate\Http\Resources\Json\JsonResource;

class PartsSaleResource extends JsonResource
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
            'title' => $this->title,
            'internal_number' => $this->internal_number,
            'amount' => $this->amount,
            'status' => $this->status,
            'source' => $this->source,
            'contacts' => $this->contacts,
            'date' => (string)$this->date,
            'cost' => (float) $this->cost,
            'parts_request_id' => $this->parts_request_id,
            'customer_id' => $this->customer_id,
            'creator_id' => $this->creator_id,
            'contractor_requisite' => $this->contractorRequisite,
            'contractor_requisite_id' => $this->contractorRequisite ? (($this->contractorRequisite instanceof IndividualRequisite ? 'individual' : 'legal') . "_{$this->contractorRequisite->id}") : null,
            'company_branch_id' => $this->company_branch_id,
            'documents_pack_id' => $this->documents_pack_id,
            'items' => $this->items,
            'customer' => $this->customer,
            'manager' => $this->manager,
            'status_lng' => $this->status_lng,
            'parts_request' => $this->partsRequest,
            'paid_sum' => (float)$this->paid_sum,
            'created_at' => (string)$this->created_at,

        ];
    }
}
