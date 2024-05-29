<?php

namespace Modules\Dispatcher\Transformers;

use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Dispatcher\Entities\Customer;
use Modules\Dispatcher\Entities\Lead;
use Modules\Orders\Transformers\CustomerOrder;

class LeadResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param \Illuminate\Http\Request
     * @return array
     */
    public function toArray($request)
    {
        $categories = $this->categories;

        if ($category = $categories->first()) {

            $date_to = (clone $this->start_date);

            $order_type = $category->pivot->order_type;
            $duration = $category->pivot->order_duration;

            if ($order_type === 'shift') {
                $date_to->addDays($duration - 1)->endOfDay();
            } else {
                $date_to->addHours($duration);
            }
        }

        if (\app()->getLocale() !== 'ru') {
            $categories->each->localization();
            $categories = $categories->sortBy('name')->values()->all();
        }

        $estimated_cost = $this->getEstimatedCost();
        return [
            'id' => $this->id,
            'customer_name' => $this->customer_name,
            'audits' => $this->audits,
            'title' => $this->title,
            'time' => $this->time,
            'phone' => $this->phone,
            'address' => $this->address,
            'comment' => $this->comment,
            'start_date' => (string)$this->start_date,
            'currency' => $this->currency,
            'can_cancel' => $this->can_cancel,
            'status' => $this->status,
            'coordinates' => $this->coordinates,
            'source' => $this->source,
            'publish_type' => $this->publish_type,
            'user_id' => $this->user_id,
            'my_vehicles' => $this->getMyVehicles(),
            'categories' => $categories,
            'status_lng' => $this->status_lng,
            'updated_at' => (string)$this->updated_at,
            'created_at' => (string)$this->created_at,
            'can_accept' => $this->canAccept(),
            'object_name' => $this->object_name,
            'tender' => $this->tender,
            'kp_date' => $this->kp_date,
            'accepted' => $this->accepted,
            'tmp_status' => $this->tmp_status,
            'first_date_rent' => $this->first_date_rent,
            'contractors' => (

            $this->status !== Lead::STATUS_CLOSE || $this->status !== Lead::STATUS_EXPIRED
                ? TbContractor::collection($this->getContractors())
                : []
            ),
            'full_address' => $this->full_address,
            'tb_winners' => $this->tb_winners,
            'main_winners' => $this->main_winners,
            'order' =>  CustomerOrder::make($this->order),
            'dispatcher_order' => DispatcherOrderInfo::make($this->dispatcher_order),
            'can_edit' => $this->can_edit,
            'offers' => $this->offers,
            'my_lead' => true,
            //'contractor_vehicles' => $this->contractor_vehicles,
            'main_contractors' => LeadMainContractorsResource::collection($this->main_contractors),
            'type' => ($this->customer instanceof Customer ? 'dispatcher' : 'client'),
            $this->mergeWhen($category, [
                'end_date' => (string)$date_to ?? '',
                'order_type' => $order_type ?? '',
                'duration' => $duration ?? '',
            ]),
            $this->mergeWhen($estimated_cost && $this->status === 'open', [
                'estimated_cost' => $estimated_cost
            ])
        ];
    }
}
