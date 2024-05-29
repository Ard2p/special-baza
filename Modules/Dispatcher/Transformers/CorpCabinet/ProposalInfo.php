<?php

namespace Modules\Dispatcher\Transformers\CorpCabinet;

use App\Machines\MachineryModel;
use App\Machines\Type;
use Illuminate\Http\Resources\Json\JsonResource as Resource;
use Modules\Dispatcher\Entities\Customer;
use Modules\Dispatcher\Entities\Lead;
use Modules\Dispatcher\Transformers\TbContractor;

class ProposalInfo extends Resource
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

        foreach ($categories as $category) {
            if($category->pivot->machinery_model_id) {
                $model = MachineryModel::query()->find($category->pivot->machinery_model_id);
                if($model) {
                    $category->name = "{$category->name} {$model->brand->name} {$model->name}";
                }

            }
        }
        $this->offers->load('user');
        return [
            'id' => $this->id,
            'internal_number' => $this->internal_number,
            'pay_type' => $this->pay_type,
            'customer_name' => $this->customer_name,
            'customer' => $this->customer,
            'title' => $this->title,
            'in_work_categories' => $this->getInWorkCategories(),
            'phone' => $this->phone,
            'start_date' => (string)$this->start_date,
            'status' => $this->status,
            'coordinates' => $this->coordinates,
            'publish_type' => $this->publish_type,
            'categories' => $categories,
            'positions' => $this->positions,
            'integration' => $this->integration,
            'status_lng' => $this->status_lng,
            'created_at' => (string)$this->created_at,
            'end_date' => (string)$this->date_to,
            'address' => $this->address,
            'full_address' => $this->full_address,
            'orders' => $this->orders,
            'can_edit' => $this->can_edit,
            $this->mergeWhen( $this->rejectType, [
                'reject_type_reason' => $this->rejectType,
                'rejected' => $this->rejected,
            ]),
            'manager' => $this->manager,
            'currency' => $this->currency,
            //'type' => ($this->customer instanceof Customer ? 'dispatcher' : 'client'),
        ];
    }
}
