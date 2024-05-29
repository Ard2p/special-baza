<?php

namespace Modules\Orders\Transformers;

use App\Service\RequestBranch;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Orders\Entities\Order;

class OrdersList extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request
     * @return array
     */
    public function toArray($request)
    {

        if($this->lead && $this->lead->company_branch_id === $this->company_branch_id) {
            $this->lead->load('customer');
        }

        $isCustomer = $this->company_branch_id === app(RequestBranch::class)->companyBranch->id;
        return [
            'id' => $this->id,
            'internal_number' => $this->internal_number,
            'customer' => $this->customer,
            'vehicles' => $this->vehicles,
            'type' => $this->type,
            'machinery_sets' => $this->machinerySets,
            'contacts' => $this->contacts,
            'categories' => $this->getOrderCategories(),
            'contract' => $this->contract,
            'vehicle_timestamps' => $this->vehicle_timestamps,
            'dispatcher_contractors' =>  $this->dispatcher_contractors,
            'date_from' => (string) $this->date_from,
            'created_at' => (string) $this->created_at,
            'sum_format' =>  $this->sum_format,
         //   'types_list_text' =>  $this->types_list_text,
            'start_date' =>  (string) $this->date_from,
            'status_lang' =>  $this->status_lang,
            'status' =>  $this->status,
            'source' =>  $this->from,
            'payment' =>  $this->payment,
            'driver' =>  $this->driver,
            'contact_person' => $this->contact_person,
            'contact_phone' =>  $this->user->phone,
            'contact_email' =>  $this->user->email,
            'customer_feedback' =>  $this->customer_feedback,
            'address' =>  $this->address,
            'object_name' =>  $this->lead->object_name,
            'amount' => $isCustomer ? ($this->contractor_sum) :  $this->amount,
            'documents' =>  $this->documents,
            'coordinates' =>  $this->coordinates,
            //'payment_expires' =>  $this->payment_expires,
           // 'time_left' =>  $this->time_left,
            'company_branch_id' => $this->company_branch_id,
            'watch_as' => $isCustomer  ? 'customer' : 'contractor',
            'tasks_count' => $this->tasks_count,
            'latest_task' => ($this->tasks->last()) ? $this->tasks->last()->date_from : null,
            $this->mergeWhen($this->lead, [
                'lead' =>  $this->lead,
            ]),
            'currency' =>  $this->currency,
            'user_id' =>  $this->user_id,
        ];
    }
}
