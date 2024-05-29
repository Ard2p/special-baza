<?php

namespace Modules\Dispatcher\Transformers\CorpCabinet;

use App\Machinery;
use App\Machines\Type;
use Illuminate\Http\Resources\Json\JsonResource as Resource;
use Modules\Orders\Entities\Order;

class OrderInfo extends Resource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request
     * @return array
     */
    public function toArray($request)
    {

        return [
            'id' => $this->id,
            'internal_number' => $this->internal_number,
            'customer' => $this->customer,
            'contractor' => $this->contractor,
            'contractor_requisite' =>  $this->contractorRequisite,
            'manager' => $this->manager,
            'contacts' => $this->contacts,
            'dispatcher_contractors' => $this->dispatcher_contractors,
            'dispatcher_categories' => $this->getDispatcherCategories(),
            'vehicles' => $this->components->where('worker_type', Machinery::class),
            'vehicles_count' => $this->getVehiclesCount(),
            'vehicle_timestamps' => $this->vehicle_timestamps,
            'date_from' => (string)$this->date_from,
            'date_to' => (string)$this->date_to,
            'created_at' => (string)$this->created_at,
            'sum_format' => $this->sum_format,
            'types_list_text' => $this->types_list_text,
            'status_lang' => $this->status_lang,
            'status' => $this->status,
            'type' => $this->type,
            'watch_as' => 'contractor',
            'company_branch_id' => $this->company_branch_id,
            'categories' => $this->getOrderCategories(),

            $this->mergeWhen($this->lead, [
                'lead' => $this->lead,
                'contractor_sum' =>  $this->contractor_sum,
                'can_add_contractor_pay' =>  $this->canAddContractorPay(),
            ]),
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



            'customer_feedback' =>  $this->customer_feedback,

            'payment_expires' =>  $this->payment_expires,
            'time_left' =>  $this->time_left,

            'user_id' =>  $this->user_id,

        ];
    }
}
