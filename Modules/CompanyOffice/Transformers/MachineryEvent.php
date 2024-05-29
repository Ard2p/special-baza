<?php

namespace Modules\CompanyOffice\Transformers;

use Illuminate\Http\Resources\Json\JsonResource;

class MachineryEvent extends JsonResource
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
            'color' => '#00a1ff',
            'title' => $this->technicalWork  ? trans('contractors/edit.repair_to') :$this->title,
            'event_type_lng' =>  $this->technicalWork  ? trans('contractors/edit.repair_to') :$this->title,
            'event_title' =>  $this->machine->name,
            'start' => $this->startDate,
            'end' => $this->endDate,
            'editable' => false,
            'type' => 'vehicle_event',
        ];
    }
}
