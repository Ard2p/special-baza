<?php

namespace Modules\Dispatcher\Transformers;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\Dispatcher\Entities\Customer;
use Modules\Dispatcher\Entities\DispatcherInvoice;
use Modules\Orders\Entities\Order;
use Modules\Orders\Entities\Service\ServiceCenter;
use Modules\Orders\Transformers\CustomerOrder;

class CustomerInfo extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param \Illuminate\Http\Request
     * @return array
     */
    public function toArray($request)
    {
        $req = $this->entity_requisites ?: $this->international_legal_requisites;
       // $orders = $this->orders;
        return [
            'id' => $this->id,
            'internal_number' => $this->internal_number,
            'last_application_id' => $this->last_application_id,
            'company_name' => $this->company_name,
            'address' => $this->address,
            'contacts' => $this->contacts,
            'region' => $this->region->name ?? '',
            'city' => $this->city->name ?? '',
            'contracts' => $this->contracts->map(function ($contract) {
                $contract->orders_count = $contract->orders()->count();
                $contract->orders_sum  = $contract->orders()->sum('amount');
                $contract->paid_sum = DispatcherInvoice::query()->whereHasMorph('owner', [Order::class])
                    ->whereIn('owner_id', $contract->orders()->select('id'))->sum('paid_sum');
                    return $contract;
            }),
            'service_contracts' => $this->serviceContracts->map(function ($contract) {
                $contract->orders_count = $contract->services()->count();
                $contract->orders_sum = $contract->services->sum('prepared_sum');
                $contract->paid_sum = DispatcherInvoice::query()->whereHasMorph('owner', [ServiceCenter::class])
                ->whereIn('owner_id', $contract->services()->select('id'))->sum('paid_sum');
                return $contract;
            }),
            'services_count' => $this->services()->count(),
            'contact_person' => $this->contact_person,
            'phone' => $this->phone,
            'email' => $this->email,
            'region_id' => $this->region_id,
            'balance_info' => $this->balance_info,
            'has_duplicate' => $this->hasDuplicate(),
            'city_id' => $this->city_id,
            'entity_requisites' => $req ? [$req] : [],
            'individual_requisites' => $this->individual_requisites ? [$this->individual_requisites] : [],
            'type' => $this->type,
            'currency' => $this->domain->currency,
            'source' => $this->source,
            'channel' => $this->channel,
            'orders_count' => $this->orders()->count(),
            'orders_sum' => $this->orders()->sum('amount'),
            'machinery_sales_count' => $this->machinerySales()->count(),
            'parts_sales_count' => $this->partsSales()->count(),
            'last_addresses' => DB::table('orders')->where([
                'customer_id' => $this->id,
                'customer_type' => Customer::class
            ])
                ->orderBy('id', 'desc')
                ->get(['id', 'address', 'coordinates', 'region_id', 'city_id'])->map(fn($order) => [
                    'id' => $order->id,
                    'address' => $order->address,
                    'coordinates' => getDbCoordinates($order->coordinates),
                    'region_id' => $order->region_id,
                    'city_id' => $order->city_id,
                ])->unique('address')->sortBy('address')->values()->all(),
       //     'orders' => CustomerOrder::collection($orders),
           // 'leads' => LeadList::collection($this->leads),
            'created_at' => (string)$this->created_at,
            'debt' => $this->calculateDebt(),
            'in_black_list' => $this->in_black_list,
            'scorings' => $this->scorings,
            'tags' => $this->tags,
            'tasks_count' => $this->tasks_count,
            'latest_task' => ($this->tasks->last()) ? $this->tasks->last()->date_from : null,
        ];
    }
}
