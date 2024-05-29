<?php

namespace Modules\Dispatcher\Transformers;

use Illuminate\Http\Resources\Json\JsonResource;
use Modules\ContractorOffice\Entities\Vehicle\Shop\MachinerySale;
use Modules\Dispatcher\Entities\DispatcherInvoice;
use Modules\Orders\Entities\Order;
use Modules\Orders\Entities\Payments\Invoice;
use Modules\Orders\Entities\Service\ServiceCenter;

class CustomerContractResource extends JsonResource
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
            'current_number' => $this->current_number,
            'requisite_instance' => $this->requisite_instance,
            'full_number' => $this->full_number,
            'number' => $this->number,
            'customer' => $this->customer,
            'customer_requisites' => $this->customer?->getRequisites(),
            'requisite' => $this->requisite,
            'created_at' => $this->created_at?->format('Y-m-d'),
            'start_date' => $this->start_date?->format('Y-m-d'),
            'end_date' => $this->end_date?->format('Y-m-d'),
            'last_application_id' => $this->last_application_id,
            'type' => $this->type,
            'subject_type' => $this->subject_type,
            'is_active' => $this->is_active,
            'count' => match ($this->type) {
                'rent' => $this->orders()->count(),
                'service' => $this->services()->count(),
                'sale' => $this->sales()->count(),
            },
            'payment_sum' => match ($this->type) {
                'rent' => DispatcherInvoice::query()->whereIn(
                    'owner_id', $this->orders()->select('id'),
                )->where('owner_type', Order::class)
                    ->sum('paid_sum'),
                'service' => DispatcherInvoice::query()->whereIn(
                    'owner_id', $this->services()->select('id'),
                )->where('owner_type', ServiceCenter::class)
                    ->sum('paid_sum'),
                'sale' => DispatcherInvoice::query()->whereIn(
                    'owner_id', $this->sales()->select('id'),
                )->where('owner_type', MachinerySale::class)
                    ->sum('paid_sum'),
            },
            'sum' => match ($this->type) {
             'rent' => $this->orders->sum('amount'),
             'service' => $this->services->sum('prepared_sum'),
             'sale' => $this->sales->sum('amount'),
            },

        ];
    }
}
