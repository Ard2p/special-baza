<?php

namespace Modules\Orders\Transformers;

use App\User\EntityRequisite;
use Illuminate\Http\Resources\Json\JsonResource as Resource;
use Modules\Orders\Entities\Order;
use Modules\Orders\Entities\OrderComponent;
use Modules\Dispatcher\Entities\Customer;

class ServiceCenterResource extends Resource
{
    /**
     * Transform the resource into an array.
     *
     * @param \Illuminate\Http\Request
     * @return array
     */
    public function toArray($request)
    {
        $actualOrder =
            $this->order instanceof OrderComponent
                ? CustomerOrder::make(Order::query()->find($this->order->order_id))
                : null;

        // TODO: исправить with чтобы убрать эту запись
        if($this->customer instanceof Customer){
            $this->customer['serviceContracts'] = $this->customer->serviceContracts;
        }

        return [
            'id'                        => $this->id,
            'internal_number'           => $this->internal_number,
            'name'                      => $this->name,
            'is_plan'                   => $this->is_plan,
            'is_warranty'               => $this->is_warranty,
            'actual_order'              => $actualOrder,
            'customer'                  => $this->customer,
            'customer_id'               => $this->customer_id,
            'machinery'                 => $this->machinery,
            'type'                      => $this->type,
            'description'               => $this->description,
            'note'                      => $this->note,
            'phone'                     => $this->phone,
            'date'                      => $this->date,
            'status'                    => $this->status,
            'status_tmp'                => $this->status_tmp,
            'technical_work'            => $this->technicalWork,
            'contract'                  => $this->contract ?? [],
            'contractor_requisite'      => $this->contractorRequisite,
            'contractor_requisite_type' => $this->contractorRequisite instanceof EntityRequisite
                ? 'legal'
                : 'individual',
            'contact_person'            => $this->contact_person,
            'bank_requisite_id'         => $this->bank_requisite_id,
            'bank_requisite'            => $this->bankRequisite,
            'company_branch_id'         => $this->company_branch_id,
            'machinery_id'              => $this->machinery_id,
            'workers'                   => $this->workers,
            'order'                     => $this->order,
            'creator_id'                => $this->creator_id,
            'created_at'                => $this->created_at,
            'updated_at'                => $this->updated_at,
            'base_id'                   => $this->base_id,
            'base'                      => $this->base,
            'manager'                   => $this->manager,
            'prepared_sum'              => $this->prepared_sum,
            'invoice_sum'               => $this->invoice_sum,
            'paid_sum'                  => $this->paid_sum,
            'address'                   => $this->address,
            'address_type'              => $this->address_type,
            'client_vehicles'              => $this->client_vehicles ?: [],
            'comment'              => $this->comment,
            'time_from'                 => $this->time_from,
            'total_works'                 => $this->total_works_sum,
            'total_parts'                 => $this->total_parts_sum,
            'time_to'                   => $this->time_to,
            'work_hours'                => $this->technicalWork?->engine_hours,
            'date_from'                 => $this->date_from
                ? $this->date_from->format('Y-m-d')
                : '',
            'documents_pack_id'         => $this->documents_pack_id,
            'documents_pack'            => $this->documentsPack,
            'date_to'                   => $this->date_to
                ? $this->date_to->format('Y-m-d')
                : '',
        ];
    }
}
