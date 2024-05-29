<?php

namespace Modules\ContractorOffice\Transformers;

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
        switch (true) {
            case !!$this->order_id:
                $type = 'order';
                $title = $this->order->name;
                $color = '#28a745';
                $manager = $this->order->manager;
                break;
            case !!$this->technical_work_id:
                $type = 'service';
                $title = $this->technicalWork->busyType->name ?? trans('contractors/edit.repair_to');
                $color = '#00a1ff';
                break;

            default:
                $type = 'busy';
                $color = $this->color;
                $title = $this->type === 'day_off'
                    ? trans('contractors/edit.day_off')
                    : trans('contractors/edit.busy');
        }
        $modelName['model_name'] = $this->machine->model ? $this->machine->model->name : $this->machine->brand->name;
        return [
            'id' => $this->id,
            'ev_id' => $this->ev_id,
            'resourceId' => $this->machine_id,
            'date_from' => $this->startDate->format('Y-m-d H:i'),
            'date_to' => $this->endDate->format('Y-m-d H:i'),
            'dt' => (string) $this->dt,
            'start' => (string) $this->startDate,
            'end' => (string) $this->endDate,
            'vehicle_id' => $this->machine_id,
            'order_id' => $this->order_id,
            'order' => $this->order ? $this->order->only('id', 'address', 'internal_number') : null,
            'type' => $type,
            'machinery' => array_merge($this->machine->only(['id', 'name', 'model_name']), $modelName),
            'title' => $this->technicalWork?->serviceCenter ? ("Заказ-наряд #{$this->technicalWork->serviceCenter->internal_number} ТО") : $title,
            $this->mergeWhen($this->technicalWork, [
                'engine_hours' => $this->technicalWork ? $this->technicalWork->engine_hours : '',
                'mechanics' => $this->technicalWork ? $this->technicalWork->mechanics : [],
                'technical_work' => $this->technicalWork,
                'service' => $this->technicalWork?->serviceCenter,
            ]),
            'event_title' => $title,
            'color' => $color,
            'manager' => $manager ?? null,
        ];
    }
}
