<?php

namespace Modules\ContractorOffice\Transformers;

use App\Machines\Type;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Auth;
use Modules\Dispatcher\Entities\DispatcherInvoice;
use Modules\Orders\Entities\Payments\InvoicePay;
use Modules\Orders\Transformers\WarehousePartSetsResource;

class Order extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param \Illuminate\Http\Request
     * @return array
     */
    public function toArray($request)
    {
        $showLead = false;
        if ($this->lead && $this->lead->user_id === $this->user_id) {
            $showLead = true;
            $this->lead->categories = Type::setLocaleNames($this->lead->categories);
            $this->lead->load('customer');
        }

        $needCheck = false;
        if($this->isAvitoOrder()){
            $logDate = Carbon::parse($this->avito_order->start_date_from)->startOfDay();
            $orderDate = Carbon::parse($this->date_from)->startOfDay();

            $needCheck = $logDate->notEqualTo($orderDate);
        }

        return [
            'id' => $this->id,
            'internal_number' => $this->internal_number,
            'customer' => $this->customer->load('scoring'),
            'balance_info' => $this->customer->balance_info,
            'manager' => $this->manager,
            'contacts' => $this->contacts,
            'channel' => $this->channel,
            'need_check' => $needCheck,
            'dispatcher_contractors' => $this->dispatcher_contractors,
            'dispatcher_categories' => $this->getDispatcherCategories(),
            'warehouse_part_sets' => WarehousePartSetsResource::collection(
                $this->warehouse_part_sets->map(function ($vehicle) {
                $vehicle->order_timestamps = $vehicle->order_timestamps->filter(function ($ts)  {
                    return $ts->order_id === $this->id;
                })->values()->all();
                return $vehicle;
            }))->toArray($request),
            'warehouse_part_sets_timestamps'  => $this->warehouse_part_set_timestamps,
            'vehicles' => $this->vehicles->map(function ($vehicle) {
                $vehicle->order_timestamps = $vehicle->order_timestamps->filter(function ($ts)  {
                    return $ts->order_id === $this->id;
                })->values()->all();
                return $vehicle;
            }),
            'driver' => $this->driver,
            'vehicles_count' => $this->getVehiclesCount(),
            'vehicle_timestamps' => $this->vehicle_timestamps,
            'date_from' => (string)$this->date_from,
            'date_to' => (string)$this->date_to,
            'created_at' => (string)$this->created_at,
            'sum_format' => $this->sum_format,
            'types_list_text' => $this->types_list_text,
            'status_lang' => $this->status_lang,
            'status' => $this->status,
            'source' => $this->from,
            'type' => $this->type,
            'watch_as' => 'contractor',
            'company_branch_id' => $this->company_branch_id,
            'categories' => $this->getOrderCategories(),

            $this->mergeWhen($showLead, [
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
            'creator' => $this->creator,
            'machinery_base' => $this->machinery_base,
            'documents' => [],
            'latest_task' => ($this->tasks->last()) ? $this->tasks->last()->date_from : null,
            'tasks_count' => $this->tasks_count,
        //    'contractor_cost' => $this->getContractorCost(),
            'coordinates' => $this->coordinates,
            'contractor_feedback' => $this->contractor_feedback->first(),
        ];
    }
}
