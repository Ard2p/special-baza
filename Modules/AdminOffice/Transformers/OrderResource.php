<?php

namespace Modules\AdminOffice\Transformers;

use App\Machines\Type;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Orders\Entities\Order;


class OrderResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param \Illuminate\Http\Request
     * @return array
     */
    public function toArray($request)
    {
        $showLead = false;
        if ($this->lead && $this->lead->user_id === $this->user_id) {
            $showLead = true;
            $this->lead->categories = Type::setLocaleNames($this->lead->categories);
            $this->lead->load('customer');
        }
        return [
            'id' => $this->id,
            'dispatcher_contractors' => $this->dispatcher_contractors,
            'dispatcher_categories' => $this->getDispatcherCategories(),
            'vehicles' => $this->vehicles,
            'vehicle_timestamps' => $this->vehicle_timestamps,
            'date_from' => (string)$this->date_from,
            'created_at' => (string)$this->created_at,
            'sum_format' => $this->sum_format,
            'types_list_text' => $this->types_list_text,
            'status_lang' => $this->status_lang,
            'status' => $this->status,
            'type' => $this->type,
            'watch_as' => 'contractor',
            'categories' => $this->getOrderCategories(),
            'lead' => $this->lead,

            'payment' => $this->payment,
            'contact_person' => $this->contact_person,
            'contact_phone' => $this->user->phone,
            'contact_email' => $this->user->email,
            'address' => $this->address,
            'amount' => $this->amount,
            'currency' => $this->currency,
            'documents' => [],
            'contractor_cost' => $this->getContractorCost(),

            'coordinates' => $this->coordinates,
            'contractor_feedback' => $this->contractor_feedback->first(),

            'customer_feedback' => $this->customer_feedback,

            'payment_expires' => $this->payment_expires,
            'time_left' => $this->time_left,
            'user_id' => $this->user_id,
            'contractor' => $this->contractor,
            'user' => $this->user,
            'contractor_sum' => $this->contractor_sum,
            'can_add_contractor_pay' => $this->canAddContractorPay(),
            'payment_url' => $this->payment->tinkoff_payment ? $this->payment->tinkoff_payment->url : null,
        ];
    }
}
