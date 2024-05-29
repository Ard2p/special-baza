<?php

namespace Modules\Dispatcher\Transformers;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Dispatcher\Entities\DispatcherInvoice;
use Modules\Dispatcher\Entities\DispatcherInvoiceDepositTransfer;
use Modules\Dispatcher\Entities\Lead;
use Modules\Orders\Entities\Order;
use Modules\Orders\Entities\Payments\InvoicePay;

class CustomersList extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request
     * @return array
     */
    public function toArray($request)
    {

        $paidSum = (intval($this->invoice_pays_sum) * 100 + intval($this->services_invoices_paid_sum)) / 100 ?: 0;
        $paidSum += intval($this->deposit_transfer_sum);

        $sum = $this->orders_sum + (($this->services_invoices_sum ?? 0) / 100);
        return [
            'id' => $this->id,
            'internal_number' => $this->internal_number,
            'contract_number' => $this->contract_number,
            'company_name' => $this->company_name,
            'address' => $this->address,
            'contact_person' => $this->contact_person,
            'phone' => $this->phone,
            'email' => $this->email,
            'created_at' => (string) $this->created_at,
            'orders_count' => intval($this->orders_count),
            'orders_sum' => ($this->orders_sum),
            'leads_count' => intval($this->leads_count),
            'in_black_list' => $this->in_black_list,
            'city' => $this->city ?? '',
            'tags' => $this->tags,
            'sum' => intval($sum),
            'manager' => $this->manager,
            'tasks_count' => intval($this->tasks_count),
            'latest_task' => ($this->tasks->last()) ? $this->tasks->last()->date_from : null,
            'sum_paid' => $paidSum,
            'sum_balance' => intval($sum) - $paidSum,
            'source' => $this->customer_source,
            'channel' => $this->customer_channel,
            'services_count' => $this->services_count,
            'machinery_sales_count' => $this->machinery_sales_count,
            'parts_sales_count' => $this->parts_sales_count,
            'services_invoices_sum' => $this->services_invoices_sum ?? 0,
            'services_invoices_paid_sum' => $this->services_invoices_paid_sum ?? 0,
        ];
    }

//    /**
//     * Get additional data that should be returned with the resource array.
//     *
//     * @param  \Illuminate\Http\Request  $request
//     * @return array
//     */
//    public function with($request)
//    {
//        return [
//            'meta' => [
//                'current_page' => $request->page,
//                'last_page' => $request->total_items / ($request->per_page ?? 15),
//                'total' => $request->total_items,
//            ],
//        ];
//    }
}
