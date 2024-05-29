<?php

namespace Modules\Dispatcher\Transformers\Lead;

use App\Machines\MachineryModel;
use App\Machines\Type;
use Illuminate\Http\Resources\Json\JsonResource as Resource;
use Modules\Dispatcher\Entities\Customer;
use Modules\Dispatcher\Entities\Lead;
use Modules\Dispatcher\Transformers\TbContractor;

class DispatcherView extends Resource
{
    /**
     * Transform the resource into an array.
     *
     * @param \Illuminate\Http\Request
     * @return array
     */
    public function toArray($request)
    {
        $categories = Type::setLocaleNames($this->categories);

        foreach ($categories as $category) {
            if ($category->pivot->machinery_model_id) {
                $model = MachineryModel::query()->find($category->pivot->machinery_model_id);
                if ($model) {
                    $category->name = "{$category->name} {$model->brand->name} {$model->name}";
                }

            }
        }
        $this->offers->load('user');
        $this->positions->each->setAppends(['category_options', 'rent_date_from', 'rent_time_from', 'request_vehicles']);
        $this->positions->loadMissing('category');
        return [
            'id'                   => $this->id,
            'audits' => $this->audits,
            'internal_number'      => $this->internal_number,
            'pay_type'             => $this->pay_type,
            'customer_name'        => $this->customer_name,
            'customer'             => $this->customer,
            'title'                => $this->title,
            'in_work_categories'   => $this->getInWorkCategories(),
            'phone'                => $this->phone,
            'comment'                => $this->comment,
            'start_date'           => (string)$this->start_date,
            'status'               => $this->status,
            'coordinates'          => $this->coordinates,
            'publish_type'         => $this->publish_type,
            'categories'           => $categories,
            'contractor_requisite' => $this->contractorRequisite,
            'positions'            => $this->positions,
            'integration'          => $this->integration,
            'status_lng'           => $this->status_lng,
            'source'               => $this->source,
            'created_at'           => (string)$this->created_at,
            'end_date'             => (string)$this->date_to,
            'address'              => $this->address,
            'full_address'         => $this->full_address,
            'orders'               => $this->orders,
            'can_edit'             => $this->can_edit,
            'my_lead'              => true,
            $this->mergeWhen($this->rejectType, [
                'reject_type_reason' => $this->rejectType,
                'rejected'           => $this->rejected,
            ]),
            'manager'              => $this->manager,
            'documents_pack_id'    => $this->documents_pack_id,
            'documents_pack'    => $this->documentsPack,
            'manager_id'           => $this->creator_id,
            'currency'             => $this->currency,
            'tmp_status'             => $this->tmp_status,
            'updated_at'             => $this->updated_at,
            'type'                 => ($this->customer instanceof Customer
                ? 'dispatcher'
                : 'client'),
            'object_name' => $this->object_name,
            'tender' => $this->tender,
            'kp_date' => $this->kp_date,
            'accepted' => $this->accepted,
            'first_date_rent' => $this->first_date_rent?->format('Y-m-d'),
            'offers'         => $this->offers,
            'my_vehicles'    => [],//$this->getVehiclesForLead(),
            'my_contractors' => [],//$this->getMyContractors(),
            'contractors'    => (

            $this->status !== Lead::STATUS_CLOSE || $this->status !== Lead::STATUS_EXPIRED
                ? TbContractor::collection($this->getContractors())
                : []
            ),

            'contract' => $this->customerContract,
        ];
    }
}
