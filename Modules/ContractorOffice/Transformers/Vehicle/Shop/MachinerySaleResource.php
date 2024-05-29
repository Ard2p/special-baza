<?php

namespace Modules\ContractorOffice\Transformers\Vehicle\Shop;

use Illuminate\Http\Resources\Json\JsonResource as Resource;

class MachinerySaleResource extends Resource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request
     * @return array
     */
    public function toArray($request)
    {
        $this->saleRequest->load('customer.entity_requisites');
        $this->load('operations.machine');
        return [
            'date' => (string) $this->date,
            'id' => $this->id,
            'internal_number' => $this->internal_number,
            'contract' => $this->contract,
            'created_at' => (string) $this->created_at,
            'pay_type' => $this->pay_type,
            'currency' => $this->currency,
            'customer' => $this->customer,
            'machinery_sale_request_id' => $this->machinery_sale_request_id,
            'account_number' => $this->account_number,
            'account_date' => $this->account_date,
            'status' => $this->status,
            'creator_id' => $this->creator_id,
            'company_branch_id' => $this->company_branch_id,
            'operations' => $this->operations,
            'amount' => $this->operations->sum('cost'),
            'sale_request' => $this->saleRequest,
        ];
    }
}
