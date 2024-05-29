<?php

namespace Modules\AdminOffice\Transformers;

use App\Machines\Type;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Dispatcher\Entities\Customer;
use Modules\Dispatcher\Entities\Lead;
use Modules\Dispatcher\Transformers\TbContractor;

class ClientLeadForOperator extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request
     * @return array
     */
    public function toArray($request)
    {
        $categories =  Type::setLocaleNames($this->categories);

        return [
            'id' => $this->id,
            'pay_type' => $this->pay_type,
            'customer_name' => $this->customer_name,
            'title' => $this->title,
            'in_work_categories' => $this->getInWorkCategories(),
            'time' => $this->time,
            'phone' => $this->phone,
            'address' => $this->address,
            'comment' => $this->comment,
            'start_date' => (string)$this->start_date,
            'currency' => $this->currency,
            'can_cancel' => $this->can_cancel,
            'status' => $this->status,
            'coordinates' => $this->coordinates,
            'publish_type' => $this->publish_type,
            'user_id' => $this->user_id,
            'categories' => $categories,
            'status_lng' => $this->status_lng,
            'created_at' => (string)$this->created_at,
            'can_accept' => $this->canAccept(),
            'contractors' => (

            $this->status !== Lead::STATUS_CLOSE || $this->status !== Lead::STATUS_EXPIRED
                ? TbContractor::collection($this->getContractors())
                : []
            ),
            'full_address' => $this->full_address,
            'orders' => $this->orders,
            'can_edit' => $this->can_edit,
            'offers' => $this->offers,
            'my_lead' => true,
            /*            'contractor_vehicles' => $this->contractor_vehicles,
                        'main_contractors' => LeadMainContractorsResource::collection($this->main_contractors),*/
            'type' => ($this->customer instanceof Customer ? 'dispatcher' : 'client'),
            'end_date' => (string) $this->date_to,

        ];
    }
}
