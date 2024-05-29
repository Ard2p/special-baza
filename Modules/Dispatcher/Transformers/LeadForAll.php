<?php

namespace Modules\Dispatcher\Transformers;

use App\Machines\Type;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Resources\Json\JsonResource as Resource;

class LeadForAll extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request
     * @return array
     */
    public function toArray($request)
    {
        $categories = $this->categories;

        if($category  = $categories->first()) {

            $date_to = (clone $this->start_date);

            $order_type = $category->pivot->order_type;
            $duration = $category->pivot->order_duration;

            if ($order_type === 'shift') {
                $date_to->addDays($duration - 1)->endOfDay();
            } else {
                $date_to->addHours($duration);
            }
        }

        $categories = Type::setLocaleNames($categories);

        $estimated_cost = $this->getEstimatedCost();
        return [
            'id' => $this->id,
         'customer_name' => $this->customer_name,
         'title' => $this->title,
            'currency' => $this->currency,
         //   'phone' => $this->phone,
            'address' => $this->address,
            'comment' => $this->comment,
            'start_date' => (string)$this->start_date,
            'status' => $this->status,
            'coordinates' => $this->coordinates,
            'publish_type' => $this->publish_type,
            'user_id' => $this->user_id,
            'my_vehicles' => $this->getMyVehicles(),
            'categories' => $categories,
            'status_lng' => $this->status_lng,
            'created_at' =>  (string) $this->created_at,
            'can_make_offer' => $this->canAccept(),
            'full_address' => $this->full_address,
            'tb_winners' => $this->tb_winners,
            'order' => $this->order,
            'has_offer' => $this->offers()->currentUser()->exists(),

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
