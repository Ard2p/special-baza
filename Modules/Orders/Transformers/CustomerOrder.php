<?php

namespace Modules\Orders\Transformers;

use App\Machinery;
use Carbon\Carbon;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Orders\Entities\Order;
use Modules\Orders\Entities\OrderComponent;
use Modules\PartsWarehouse\Entities\Warehouse\WarehousePartSet;
use Modules\PartsWarehouse\Transformers\RentWarehouseSetPartResource;
use Modules\PartsWarehouse\Transformers\RentWarehouseSetResource;

class CustomerOrder extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param \Illuminate\Http\Request
     * @return array
     */
    public function toArray($request)
    {
        $myLead = true;

        // }
        $vehicles =
            $this->components->load('ins_certificate')->where('worker_type', Machinery::class)->map(function ($item) {
                $item->date_from = $item->date_from->toDateTimeString();
                $item->date_to = $item->date_to->toDateTimeString();
                $item->worker->contractor = $item->worker->subOwner;
                $item->worker->load('avito_ads');
                $item->worker->append(['sum_hour','sum_day']);
                $item->worker->wialon_telematic =
                    $item->worker->wialon_telematic
                        ?: null;
                $item->worker->optional_attributes = $item->worker->optional_attributes->map(function ($item) {
                    if(is_string($item)) {
                        return  $item;
                    }
                    return $item->pivot->value ? $item->full_name : null;
                })->filter(fn($item) => !!$item)->values();
                return $item;
            })->values()->all();

        $warehouse_part_sets =
            $this->components->load('ins_certificate')->where('worker_type', WarehousePartSet::class)->map(function (OrderComponent $item) {
                $item->setRelation('parts',[]);
                $item->parts = RentWarehouseSetPartResource::collection($item->rent_parts->where('type','rent'));
                $item->worker->currency = 'RUB';
                $item->worker->contractor = $item->worker->subOwner;
                $item->worker->wialon_telematic =
                    $item->worker->wialon_telematic
                        ?: ['id' => ''];
                return $item;
            });

        $this->customer->load('contracts', 'contacts');
        $this->customer->contract = $this->customer->contracts->where('requisite_type', get_class($this->contractorRequisite))
            ->where('requisite_id', $this->contractorRequisite->id)->first();
        $services = 0;
        $this->components->each(function ($item) use
        (
            &
            $services
        ) {
            foreach ($item->services as $service) {
                if ($service->customService->is_pledge)
                    $services += $service->price;
            }
        });
        if($this->lead) {
            $this->lead->load('contract');
        }
        $this->machinerySets->each->setAppends(['name']);
        $pays = $this->invoices->flatMap(fn($invoice) => $invoice->pays);

        $this->principal?->load('person');

        $needCheck = false;
        $avito_order = null;
        if($this->isAvitoOrder() && $this->avito_order){
            $logDate = Carbon::parse($this->avito_order->start_date_from)->startOfDay();
            $orderDate = Carbon::parse($this->date_from)->startOfDay();

            $needCheck = $logDate->notEqualTo($orderDate);
            $avito_order = $this->avito_order;
            $avito_order->customer_type = $this->avito_order->customer_type_name;
        }

        return [
            'id'                   => $this->id,
            'audits'               => $this->audits()->with('user')->orderBy('id', 'desc')->get(),
            'external_id'          => $this->external_id,
            'bank_requisite'       => $this->bankRequisite,
            'avito_order'          => $avito_order,
            'return_parts'         => $this->return_parts,
            'internal_number'      => $this->internal_number,
            'media'                => $this->media,
            'name'                 => $this->name,
            'need_check'                 => $needCheck,
            'channel'                 => $this->channel,
            'source'                 => $this->from,
            'vehicles'             => $vehicles,
            'invoices'             => $this->invoices,
            'warehouse_part_sets'  => $warehouse_part_sets->toArray(),
            'warehouse_part_sets_timestamps'  => $this->warehouse_part_set_timestamps,
            'type'                 => $this->type,
            'contacts'             => $this->contacts,
            'customer' => $this->customer->load('scoring'),
            'balance_info'         => $this->customer->balance_info,
            'contractor_requisite' => $this->contractorRequisite,
            'has_invoices'         => $this->has_invoices,
            'contract'         => $this->contract,
            'contract_number'         => $this->contract_number,
            //  'dispatcher_contractors' => $this->getDispatcherContractors(),
            'categories'           => $this->getOrderCategories(),
            'driver'           => $this->driver,
            'vehicle_timestamps'   => $this->vehicle_timestamps,
            'date_from'            => $this->date_from,
            'date_to'              => $this->date_to,
            'created_at'           => $this->created_at->toDateTimeString(),
            'sum_format'           => $this->sum_format,
            'types_list_text'      => $this->types_list_text,
            'status_lang'          => $this->status_lang,
            'status'               => $this->status,
            'payment'              => $this->payment,
            'machinery_sets'       => $this->machinerySets,
            'set_prices'           => $this->set_prices,
            'manager'              => $this->manager,
            'principal'            => $this->principal,
            'principal_id'         => $this->principal_id,
            'contact_person'       => $this->contact_person,
            'contact_phone'        => $this->user->phone,
            'contact_email'        => $this->user->email,
            'customer_feedback'    => $this->customer_feedback,
            'address'              => $this->address,
            'amount'               => $this->amount,
            'documents'            => $this->documents,
            'coordinates'          => $this->coordinates,
            'payment_expires'      => $this->payment_expires,
            'time_left'            => $this->time_left,
            'company_branch_id'    => $this->company_branch_id,
            'creator'              => $this->creator,
            'comment'              => $this->comment,
            'machinery_base'       => $this->machinery_base,
            'work_type'       => $this->work_type,
            'watch_as'             => 'customer',
            'tasks_count'          => $this->tasks_count,
            'status_date'          => $this->status_date,
            'latest_task'          => ($this->tasks->last())
                ? $this->tasks->last()->date_from
                : null,
            'pledge'               => [
                  'in' => (float) $pays
                    ->where('method', 'pledge')
                    ->where('operation', 'in')
                    ->sum('sum'),
                'out' => (float) $pays
                    ->where('method', 'pledge')
                    ->where('operation', 'out')
                    ->sum('sum'),
                'total' => $services,
            ],
            'contractor_pays_sum' => $this->components->sum('contractor_pays_sum'),
            'value_added' => (float) $this->components->sum('value_added'),
            'status_fake' => $this->tmp_status,
            //   'contractor_cost' => $this->getContractorCost(),
            $this->mergeWhen($myLead, [
                'lead' => $this->lead,
                'lead_positions' => $this->lead->positions->each->setAppends(['category_options', 'rent_date_from', 'rent_time_from', 'request_vehicles']),
            ]),
            'currency'             => $this->currency,
            'manager_id'           => $this->creator_id,
            $this->mergeWhen(in_array($this->status, [Order::STATUS_ACCEPT, Order::STATUS_DONE]), [
                'contractor' => $this->contractor,
            ]),
            $this->mergeWhen($this->lead, [
                'contractor_sum'         => $this->contractor_sum,
                'can_add_contractor_pay' => $this->canAddContractorPay(),
            ]),
            'payment_url'          => $this->payment->tinkoff_payment
                ? $this->payment->tinkoff_payment->url
                : null,
        ];
    }
}
