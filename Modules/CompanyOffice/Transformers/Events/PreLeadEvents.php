<?php

namespace Modules\CompanyOffice\Transformers\Events;

use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Dispatcher\Entities\Lead;
use Modules\Dispatcher\Entities\PreLead;

class PreLeadEvents extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request
     * @return array
     */
    public function toArray($request)
    {
        switch ($this->status) {
            case PreLead::STATUS_ACCEPT:
                $color = '#28a745';
                break;
            case PreLead::STATUS_OPEN:
                $color = 'black';
                break;
            case PreLead::STATUS_REJECT:
                $color = '#dc3545';
                break;
            default: $color = 'black';
        }
        return [
            'id' => $this->id,
            'color' =>  $color,
            'title' =>"Запрос на аренду #{$this->internal_number}",
            'description' => $this->comment,
            'customer' => $this->customer,
            'event_type_lng' =>  "Запрос на аренду #{$this->internal_number}",
            'event_title' =>  $this->title,
            'start' => $this->date_from,
            'end' => $this->date_to,
            'address' => $this->address,
            'editable' => false,
            'status' => '',
            'manager' => $this->manager,
            'type' => 'prelead',
        ];
    }
}
