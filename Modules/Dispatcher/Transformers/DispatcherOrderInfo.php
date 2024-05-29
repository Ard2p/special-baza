<?php

namespace Modules\Dispatcher\Transformers;

use App\Machines\Type;
use Illuminate\Http\Resources\Json\JsonResource;

class DispatcherOrderInfo extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param \Illuminate\Http\Request
     * @return array
     */
    public function toArray($request)
    {

        $category = $this->categories->first();
        $this->categories = Type::setLocaleNames($this->categories);
        if($category) {

            $date_to = (clone $this->start_date);

            $order_type = $category->pivot->order_type;
            $duration = $category->pivot->order_duration;

            if ($order_type === 'shift') {
                $date_to->addDays($duration - 1)->endOfDay();
            } else {
                $date_to->addHours($duration);
            }
        }

        $this->customer->entity_requisites = $this->customer->entity_requisites()->first();

        return [
            'id' => $this->id,
            'customer_name' => $this->customer_name,
            'phone' => $this->phone,
            'address' => $this->address,
            'comment' => $this->comment,
            'status' => $this->status,
            'start_date' => (string) $this->start_date,
            'region_id' => $this->region_id,
            'city_id' => $this->city_id,
            'user_id' => $this->user_id,
            'customer_id' => $this->customer_id,
            'created_at' => (string) $this->created_at,
            'is_paid' => $this->is_paid,
            'details_link' => $this->details_link,
            'amount' => $this->amount,
            'contractor_sum' => $this->contractor_sum,
            'currency' => $this->currency,
            'lead_id' => $this->lead ? $this->lead->id : null,
            'documents' => [],
            'has_invoices' => (bool) $this->invoices()->count(),
            'categories' => $this->categories,
            'status_lang' => $this->status_lang,
            'customer' => $this->customer,
            'contractor' => $this->contractor,
            'can_add_contractor_pay' => $this->canAddContractorPay(),
            $this->mergeWhen($category, [
                'end_date' => (string)$date_to ?? '',
                'order_type' => $order_type ?? '',
                'duration' => $duration ?? '',
            ]),
        ];
    }
}
