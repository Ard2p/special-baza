<?php

namespace Modules\Dispatcher\Transformers;

use App\Service\RequestBranch;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\CompanyOffice\Entities\Company\CompanyBranch;

class LeadList extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param \Illuminate\Http\Request
     * @return array
     */
    public function toArray($request)
    {

        $myLead = $this->company_branch_id === app()->make(RequestBranch::class)->companyBranch->id;
        $this->positions->each->setAppends(['category_options', 'rent_date_from', 'rent_time_from']);

        return [
            'id'                 => $this->id,
            'internal_number'    => $this->internal_number,
            'title'              => $this->title,
            'customer'           => $this->customer,
            'customer_name'      => ($this->customer instanceof CompanyBranch
                ? $this->customer->name
                : $this->customer->company_name),
            'contact_person'     => $this->customer->contact_person,
            'phone'              => $this->phone,
            'address'            => $this->address,
            'comment'            => $this->comment,
            'publish_type'       => $this->publish_type,
            'start_date'         => (string)$this->start_date,
            'status'             => $this->status,
            'tmp_status'             => $this->tmp_status,
            'status_date'        => $this->status_date,
            'source'             => $this->source,
            'coordinates'        => $this->coordinates,
            'user_id'            => $this->user_id,
            'categories'         => $this->positions,
            'positions'         => $this->positions,
            'status_lng'         => $this->status_lng,
            'created_at'         => (string)$this->created_at,
            'full_address'       => $this->full_address,
            'dispatcher_sum'     => $this->dispatcher_sum,
            'can_edit'           => $this->can_edit,
            'my_lead'            => $myLead,
            'reject_type'        => $this->reject_type,
            'reject_type_reason' => $this->rejectType,
            'rejected'           => $this->rejected,
            'object_name' => $this->object_name,
            'tender' => $this->tender,
            'kp_date' => $this->kp_date,
            'accepted' => $this->positions->filter(fn($position) => $position->accepted)->isNotEmpty(),
            'first_date_rent' => $this->first_date_rent,

            'can_accept' => !$this->isArchived()
                ? $this->canAccept()
                : false,

            'end_date' => (string)$this->date_to,


            $this->mergeWhen($this->pivot, [
                'pivot' => $this->pivot
            ]),

            $this->mergeWhen($myLead, [
                'manager' => $this->manager
            ]),
        ];
    }
}
