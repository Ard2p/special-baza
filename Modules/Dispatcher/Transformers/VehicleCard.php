<?php

namespace Modules\Dispatcher\Transformers;

use Illuminate\Http\Resources\Json\JsonResource;

class VehicleCard extends JsonResource
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
            'name' => $this->name,
            'url' => $this->rent_url,
            'order_cost' => $this->order_cost,
            'date_from' => $this->date_from,
            'date_to' => $this->date_to,
            'user_id' => $this->user_id
        ];
    }
}
