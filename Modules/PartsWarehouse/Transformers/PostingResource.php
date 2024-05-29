<?php

namespace Modules\PartsWarehouse\Transformers;

use Illuminate\Http\Resources\Json\JsonResource;

class PostingResource extends JsonResource
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
            'pay_type' => $this->pay_type,
            'date' => $this->date,
            'account_number' => $this->account_number,
            'account_date' => $this->account_date,
            'provider' => $this->provider,
            'amount' => $this->amount,
            'cost' => $this->cost,
            $this->mergeWhen($this->relationLoaded('stockItems'), fn() => ['stock_items' => $this->stockItems])
        ];
    }
}
