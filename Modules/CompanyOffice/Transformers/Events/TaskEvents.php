<?php

namespace Modules\CompanyOffice\Transformers\Events;

use Illuminate\Http\Resources\Json\JsonResource;

class TaskEvents extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request
     * @return array
     */
    public function toArray($request)
    {


        $binds = [];
        if ($this->orders->isNotEmpty()) {
            $order = $this->orders->first();
            $binds[] = [
                'id' => $order->id,
                'internal_number' => $order->internal_number,
                'type' => 'order',
            ];
        }
        if ($this->leads->isNotEmpty()) {
            $lead = $this->leads->first();
            $binds[] = [
                'id' => $lead->id,
                'type' => 'lead',
            ];
        }
        if ($this->vehicles->isNotEmpty()) {
            $vehicle = $this->vehicles->first();
            $binds[] = [
                'id' => $vehicle->id,
                'type' => 'vehicle',
                'name' => $vehicle->name,
                'isBusy' => $this->vehicleCalendar()->exists(),
            ];
        }
        if ($this->customers->isNotEmpty()) {
            $customer = $this->customers->first();
            $binds[] = [
                'id' => $customer->id,
                'type' => 'client',
                'name' => $customer->company_name,
            ];
        }
        return [
            'id' => $this->id,
            'color' => '#f37153',
            'title' => $this->title,
            'event_type_lng' => trans('contractors/edit.task'),
            'event_title' => $this->title,
            'description' => $this->description,
            'start' => $this->date_from,
            'end' => $this->date_to,
            'editable' => false,
            'important' => $this->important,
            'type' => 'task',
            'duration' => $this->duration,
            'task_type' => $this->task_type,
            'employee_id' => $this->employee_id,
            'employee_name' => ($this->employee) ? $this->employee->name : null,
            'status' => $this->status_name,
            'status_id' => $this->status,
            'updated_by_id' => $this->updated_by_id,
            'updated_by_name' => ($this->updated_by) ? $this->updated_by->name : null,
            'responsible_id' => $this->responsible_id,
            'responsible_name' => ($this->responsible) ? $this->responsible->name : null,
            'binds' => $binds,
        ];
    }
}
