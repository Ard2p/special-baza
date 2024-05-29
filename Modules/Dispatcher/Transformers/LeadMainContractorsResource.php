<?php

namespace Modules\Dispatcher\Transformers;

use Illuminate\Http\Resources\Json\JsonResource;

class LeadMainContractorsResource extends JsonResource
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
            'id'  => $this->id,
            'phone'  => $this->phone,
            'categories'  => [],
            'company_name'  => $this->company_name,
        ];
    }
}
