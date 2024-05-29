<?php

namespace Modules\Dispatcher\Transformers\Lead;

use App\Machines\Type;
use App\Service\RequestBranch;
use Illuminate\Http\Resources\Json\JsonResource as Resource;
use Illuminate\Support\Facades\Auth;
use Modules\Dispatcher\Entities\Customer;
use Modules\Dispatcher\Entities\Lead;
use Modules\Dispatcher\Transformers\DispatcherOrderInfo;
use Modules\Dispatcher\Transformers\LeadMainContractorsResource;
use Modules\Dispatcher\Transformers\TbContractor;
use Modules\Orders\Transformers\CustomerOrder;

class ContractorView extends Resource
{
    /**
     * Transform the resource into an array.
     *
     * @param \Illuminate\Http\Request
     * @return array
     */
    public function toArray($request)
    {
        $categories =  Type::setLocaleNames($this->categories);

        return [
            'id' => $this->id,
            'pay_type' => $this->pay_type,
            'customer_name' => $this->customer_name,
            'in_work_categories' => $this->getInWorkCategories(),
            'title' => $this->title,
            'time' => $this->time,
            'phone' => $this->phone,
            'address' => $this->address,
            'comment' => $this->comment,
            'start_date' => (string)$this->start_date,
            'tmp_status' => $this->tmp_status,
            'updated_at' => (string) $this->updated_at,
            'currency' => $this->currency,
            'can_cancel' => $this->can_cancel,
            'status' => $this->status,
            'positions' => $this->positions,
            'coordinates' => $this->coordinates,
            'publish_type' => $this->publish_type,
            'user_id' => $this->user_id,
            'my_vehicles' => $this->getVehiclesForLead(Auth::id()),
            'categories' => $categories,
            'status_lng' => $this->status_lng,
            'created_at' => (string)$this->created_at,
            'can_accept' => $this->canAccept(),
            'full_address' => $this->full_address,
            'offer' => $this->offers->where('user_id', Auth::id())->all(),

            'my_contractors' => $this->getMyContractors(app()->make(RequestBranch::class)->companyBranch),
            'type' => ($this->customer instanceof Customer ? 'dispatcher' : 'client'),

            'contract' => $this->customerContract,

        ];
    }
}
