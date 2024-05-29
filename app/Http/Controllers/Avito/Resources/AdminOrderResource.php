<?php

namespace App\Http\Controllers\Avito\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use App\Http\Controllers\Avito\Resources\OrderResource;
use Modules\ContractorOffice\Entities\Vehicle\Price;

/**
 * @OA\Schema(
 *  @OA\Xml(name="AdminOrderResource")
 * )
 **/
class AdminOrderResource extends JsonResource
{
    public function toArray($request)
    {
        $machineryTypes = [];
        $componentsOrder = [];
        $avito_rent_total_cost = 0;
        $avito_rent_total_cost_without_vat = 0;

        $order = $this->order;
        if($order) {
            $components = $order->workers;
            foreach ($components as $component) {
                $machineryTypes[] = $component->worker->name;
                $componentsOrder[] = [
                    'id' => $component->worker->id,
                    'name' => $component->worker->name,
                    'status' => $component->status,
                    'total_sum' => $component->total_sum,
                    'reject_type' => $component->reject_type,
                    'reject_type_message' => $component->reject_type ? trans('transbaza_statuses.proposal_reject_' . $component->reject_type) : null,
                    'finish_date' => $component->finish_date,
                ];

                $avito_rent_total_cost += $component->total_sum;
                $avito_rent_total_cost_without_vat += $component->total_sum_without_vat;
            }
            $machineryTypes = array_unique($machineryTypes);
        }

        if($this->order?->customer && $this->order?->customer->hasRequisite()) {
            if($this->order?->customer->entity_requisites) $requisites_type = 'entity';
            elseif ($this->order?->customer->individual_requisites) $requisites_type = 'individual';
            elseif ($this->order?->customer->international_legal_requisites) $requisites_type = 'legal';
        }

        $finishDate = $this->order?->components()->max('finish_date');

        return [
            'id' => $this->id,
            'avito_ad_id' => $this->avito_ad_id,
            'avito_order_id' => $this->avito_order_id,

            'avito' => [
                'avito_rent_total_cost' => $avito_rent_total_cost,
                'avito_rent_total_cost_without_vat' => $avito_rent_total_cost_without_vat
            ],

            'created_at' => $this->created_at,
            'order_id' => $this->order_id,

            'completed_at' => $finishDate,
            'updated_at' => $this->return_sum ? $this->updated_at : '',
            'status' => $this->status,
            'cancel_reason' => $this->cancel_reason,
            'cancel_reason_message' => $this->cancel_reason_message,
            'canceled_at' => $this->canceled_at,

            'city' => $this->order?->address,
            'return_sum' => $this->order?->pays->where('operation', 'out')->sum('sum'),
            'pays_in_sum' => $this->order?->pays->where('operation', 'in')->sum('sum'),
            'amount_vat' => $this->order?->amount,
            'invoices_sum' => $this->order?->invoices->sum('sum'),
            'invoice_date' => $this->order?->invoices->first()?->created_at,
            'paid_sum' => $this->order?->invoices->sum('paid_sum'),
            'pay_method' => $this->order?->invoices->first()?->pays->first()?->method,
            'pay_date' => $this->order?->invoices->first()?->pays->first()?->created_at,

            'avito_dotation_sum' => $this->order?->workers->sum('avito_dotation_sum'),
            'delivery_cost' => $this->order?->workers->sum('delivery_cost') + $this->order?->workers->sum('return_delivery'),
            'return_delivery' => $this->order?->workers->sum('return_delivery'),

            'inn' => $this->inn,

            'contractor' => [
                'company_name' => $this->order?->contractorRequisite->short_name ?? $this->order?->contractorRequisite->name,
                'company_alias' => $this->order?->contractor->company->alias,
                'company_branch_id' => $this->order?->company_branch_id,
            ],

            'customer' => [
                'name' => $this->order?->customer?->name,
                'company_name' => $this->order?->customer?->company_name,
                'requisites_type' => $requisites_type ?? null,
                'phone' => $this->order?->customer?->phone,
            ],

            'machinery_types' => $machineryTypes ?? null,

            'components' => $componentsOrder,

            'vat' => $this->order?->contractorRequisite?->vat_system === Price::TYPE_CASHLESS_VAT,

            'order' => [
                'created_at' => $this->order?->created_at,
                'date_from' => $this->order?->date_from,
                'status' => $this->order?->status,
                'source' => $this->order?->from,
                'link' => $this->order?->generateCompanyLink()
            ],
        ];
    }
}
