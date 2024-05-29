<?php

namespace Modules\Orders\Transformers;

use App\Machinery;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Orders\Entities\Order;

class WarehousePartSetsResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request
     * @return array
     */
    public function toArray($request)
    {
        //xdebug_break();
//        $amount = $this->pivot->cost_per_unit * $this->pivot->order_duration;
//        $total_sum = $amount + $this->pivot->delivery_cost + $this->pivot->return_delivery;
//        $component = $this->orders;
        return [
            'complete' => $this->pivot->complete,
            'worker_id' => $this->pivot->worker_id,
            'date_from' => $this->pivot->date_from,
            'date_to' => $this->pivot->date_to,
            'application_id' => $this->pivot->application_id,
            'comment' => $this->pivot->comment,
            "order_type" => $this->pivot->order_type,
            "order_duration" => $this->pivot->order_duration,
            'worker' => WarehousePartSetsWorkerResource::make($this->resource),
            'parts' => WarehousePartSetsPartResource::collection($this->parts),
            'idle_periods' => $this->pivot->idle_periods ?? [],
            'histories' => $this->pivot->histories ?? [],
            'media' => $this->pivot->media ?? [],
            'amount' => $amount,
            'amount_without_vat' => $amount,
            'total_sum' => $total_sum,
            'total_sum_without_vat' => $this->pivot->total_sum_without_vat,
            'delivery_cost' => $this->pivot->delivery_cost,
            'return_delivery' => $this->pivot->return_delivery,
            'delivery_cost_without_vat' => $this->pivot->delivery_cost,
            'return_delivery_without_vat' => $this->pivot->return_delivery,
        ];
    }
}
