<?php

namespace Modules\CompanyOffice\Transformers\Events;

use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Dispatcher\Entities\Lead;

class LeadEvents extends JsonResource
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
            case Lead::STATUS_ACCEPT:
                $color = '#28a745';
                break;
            case Lead::STATUS_OPEN:
                $color =  '#ffb021';//'#5f80bc';
                break;
            default: $color = 'grey';
        }
        return [
            'id' => $this->id,
            'color' => $this->rejectType ? '#dc3545' : $color,
            'title' => trans('transbaza_proposal.id', ['id' => $this->internal_number]),
            'description' => $this->comment,
            'customer' => $this->customer,
            'event_type_lng' =>  trans('transbaza_proposal.id', ['id' => $this->internal_number]),
            'event_title' =>  $this->title,
            'start' => $this->start_date,
            'end' => $this->date_to,
            'address' => $this->address,
            'editable' => false,
            'status' => $this->status_lng,
            'manager' => $this->manager,
            'type' => 'lead',
        ];
    }
}
