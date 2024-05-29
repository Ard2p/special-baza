<?php

namespace Modules\Dispatcher\Transformers;

use App\Machines\Type;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderList extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request
     * @return array
     */
    public function toArray($request)
    {
        $category = $this->getOrderCategories()->first();

        $this->categories = Type::setLocaleNames($this->getOrderCategories());
       /* if($category) {


            $date_to = $category->pivot->date_to;

            $order_type = $category->pivot->order_type;
            $duration = $category->pivot->order_duration;

            if ($order_type === 'shift') {
                $date_to->addDays($duration - 1)->endOfDay();
            } else {
                $date_to->addHours($duration);
            }
        }*/
        return [
            'id' => $this->id,
            'customer_name' => $this->customer_name,
            'phone' => $this->phone,
            'status_lang' => $this->status_lang,
            'address' => $this->address,
            'comment' => $this->comment,
            'start_date' => (string) $this->date_from,
            'end_date' => (string) $this->date_to,
            'status' => $this->status,
            'customer' => $this->customer,
            'contractor' => $this->contractor,
            'user_id' => $this->user_id,
            'categories' => $this->categories,
            'status_lng' => $this->status_lng,
            'is_paid' => $this->is_paid,
            'created_at' =>  (string) $this->created_at,
            'full_address' => $this->full_address,
            'amount' => $this->amount,
            'currency' => $this->currency,

            $this->mergeWhen($category, [
                'end_date' => $date_to ?? '',
                'order_type' => $order_type ?? '',
                'duration' => $duration ?? '',
            ]),
            $this->mergeWhen($this->pivot, [
                'pivot' => $this->pivot
            ]),
        ];
    }
}
