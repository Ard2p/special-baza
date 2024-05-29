<?php

namespace Modules\ContractorOffice\Transformers;

use Illuminate\Http\Resources\Json\JsonResource;

class MachineryLeadEvent extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request
     * @return array
     */
    public function toArray($request)
    {
        $vehicle = [
            "id" => null,
            "name" => null,
            "model_name" => null,
        ];
        $position = $this->lead->positions->first();
        if ($position && $position->vehicles->first()) {
            $v = $position->vehicles->first();
            $modelName['model_name'] = $v->model ? $v->model->name : $v->brand->name;
            $vehicle = array_merge($v->only(['id', 'name', 'model_name']), $modelName);
        }

        return [
            'id' => $this->id,
            'internal_number' => $this->internal_number,
            'date_from' => $this->date_from->format('Y-m-d H:i'),
            'date_to' => $this->date_to->format('Y-m-d H:i'),
            'start' => (string) $this->date_from,
            'dt' => (string) $this->dt,
            'end' => (string) $this->date_to,
            'type' => 'lead',
            'lead_id' => $this->lead->id,
            'title' => trans('transbaza_order.reserve')." #{$this->lead->internal_number}",
            'event_title' => trans('transbaza_order.reserve'),
            'color' => '#ffb021',
            'manager' => $this->lead->manager,
            'machinery' => $vehicle,

        ];
    }
}
