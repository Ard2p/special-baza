<?php

namespace Modules\AdminOffice\Http\Resources;

use App\Http\Controllers\Avito\Resources\OrderResource;
use Carbon\Carbon;
use Illuminate\Http\Resources\Json\JsonResource;

class AdminAvitoOrderResource extends JsonResource
{
    public function toArray($request)
    {
        $finishDate = $this->order?->components()->max('finish_date');
        return [
            'id' => $this->id,
            'avito_ad_id' => $this->avito_ad_id,
            'avito_order_id' => $this->avito_order_id,
            'created_at' => $this->created_at,
            'company_branch' => $this->company_branch,
            'order_id' => $this->order_id,
            'customer' => $this->customer?->company_name,
            'phone' => $this->customer?->phone ? '+' . $this->customer->phone : '',
            'amount_vat' => $this->order?->amount,
            'invoices_sum' => $this->order?->invoices->sum('sum'),
            'invoice_date' => $this->order?->invoices->first()?->created_at,
            'paid_sum' => $this->order?->invoices->sum('paid_sum'),
            'pay_method' => $this->order?->invoices->first()?->pays->first()?->method,
            'pay_date' => $this->order?->invoices->first()?->pays->first()?->created_at,
            'completed_at' => $finishDate,
            'return_sum' => $this->return_sum,
            'updated_at' => $this->return_sum ? $this->updated_at : '',
            'status' => $this->status,
            'status_description' => $this->status,
            'cancel_reason' => $this->cancel_reason,
            'cancel_reason_message' => $this->cancel_reason_message,
            'canceled_at' => $this->canceled_at,
            $this->mergeWhen($this->order, fn() => [
                'json' => OrderResource::make($this)
            ]),
        ];
    }
}
