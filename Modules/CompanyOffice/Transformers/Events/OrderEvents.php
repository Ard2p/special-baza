<?php

namespace Modules\CompanyOffice\Transformers\Events;

use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Orders\Entities\Order;

class OrderEvents extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param \Illuminate\Http\Request
     * @return array
     */
    public function toArray($request)
    {

        switch ($this->status) {
            case Order::STATUS_ACCEPT:
                $color = '#28a745';
                break;
            default: $color = 'grey';
        }
        return [
            'id' => $this->id,
            'color' => $color,
            'title' =>   $this->name,
            'event_type_lng' =>  $this->name,
            'event_title' => $this->customer->comany_name ?: $this->customer->name,
            'start' => $this->date_from,
            'end' => $this->date_to,
            'status' => $this->status_lang,
            'editable' => false,
            'order_amount' => $this->amount,
            'customer' => $this->customer,
            'address' => $this->address,
            'manager' => $this->manager,
            'type' => 'order',
        ];
    }
}
