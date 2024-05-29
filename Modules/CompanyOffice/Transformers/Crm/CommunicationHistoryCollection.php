<?php

namespace Modules\CompanyOffice\Transformers\Crm;

use App\User;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Modules\Dispatcher\Entities\Lead;
use Modules\Dispatcher\Entities\PreLead;
use Modules\Orders\Entities\Order;

class CommunicationHistoryCollection extends JsonResource
{
    /**
     * Transform the resource collection into an array.
     *
     * @param  \Illuminate\Http\Request
     * @return array
     */
    public function toArray($request)
    {

        if($this->bind) {
            switch (true) {
                case $this->bind instanceof Order:
                    $bind = 'order';
                    break;
                case $this->bind instanceof Lead:
                    $bind = 'lead';
                    break;
                case $this->bind instanceof PreLead:
                    $bind = 'prelead';
                    break;
            }
        }

        $link = '';
        /** @var User $user */
        $user = \Auth::guard('api')->user();
        if(in_array($this->company->alias, ['rental-ing', 'vega']) && $this->company()->userHasAccess($user->id)->exists()) {
            $link = $this->link;
        };
        if(!in_array($this->company->alias, ['rental-ing', 'vega'])) {
            $link = $this->link;
        }
        return [
            'id' => $this->id,
            'phone' => $this->phone,
            'manager_phone' => $this->manager_phone,
            'link' =>$link,
            'status' => $this->status,
            'customers' => $this->getCustomers(),
            'raw_data' => $this->raw_data,
            'has_orders' => $this->has_orders,
            'important' => $this->important,
            'listened' => $this->listened,
            'is_hidden' => $this->is_hidden,
            'updated_at' => (string) $this->updated_at,
            'created_at' => (string) $this->created_at,
            'bind' => $bind ?? null,
            'bind_id' => $this->bind_id,
        ];
    }
}
