<?php

namespace Modules\AdminOffice\Transformers;

use App\Machines\Type;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Dispatcher\Entities\Customer;
use Modules\Dispatcher\Entities\Lead;
use Modules\Dispatcher\Transformers\DispatcherOrderInfo;
use Modules\Dispatcher\Transformers\LeadMainContractorsResource;
use Modules\Dispatcher\Transformers\TbContractor;
use Modules\Orders\Transformers\CustomerOrder;

class LeadForOperator extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request
     * @return array
     */
    public function toArray($request)
    {
        $categories = Type::setLocaleNames($this->categories);

        $this->offers->load('user');
        return [
            'id' => $this->id,
            'pay_type' => $this->pay_type,
            'customer_name' => $this->customer_name,
            'title' => $this->title,
            'in_work_categories' => $this->getInWorkCategories(),
            'phone' => $this->phone,
            'start_date' => (string)$this->start_date,
            'status' => $this->status,
            'coordinates' => $this->coordinates,
            'publish_type' => $this->publish_type,
            'categories' => $categories,
            'status_lng' => $this->status_lng,
            'created_at' => (string)$this->created_at,
            'end_date' => (string)$this->date_to,
            'address' => $this->address,
            'full_address' => $this->full_address,
            'orders' => $this->orders,
            'can_edit' => $this->can_edit,
            'my_lead' => true,
            'currency' => $this->currency,
            'user_id' => $this->user_id,
            'type' => ($this->customer instanceof Customer ? 'dispatcher' : 'client'),

            'offers' => $this->offers,
            'my_vehicles' => $this->getVehiclesForLead(),
            'my_contractors' => $this->getMyContractors(),
            'contractors' => (

            $this->status !== Lead::STATUS_CLOSE || $this->status !== Lead::STATUS_EXPIRED
                ? TbContractor::collection($this->getContractors())
                : []
            ),
        ];
    }
}
