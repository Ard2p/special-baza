<?php

namespace App\Http\Controllers\Avito\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

/**
 * @OA\Schema(
 *  @OA\Xml(name="OrderResource")
 * )
 **/
class OrderInvoiceResource extends JsonResource
{
    /*
     * @param  \Illuminate\Http\Request
     * @return array
     */
    public function toArray($request)
    {
        $paymentType = null;

        if ($this->pays->count()) {
            $paymentType = $this->pays->firstWhere('method', 'bank') ? 2 : 1;
        }

        return [
            "invoice_id" => $this->id,
            "invoice_number" => $this->number,
            "invoice_status" => $this->is_paid ? 2 : 1,
            "invoice_date" => $this->created_at,
            "payment_type" => $paymentType,
            "online_payment_url" => $this->system_payment?->details->formUrl,
            "invoice_url" => ($request->inv_url) ? Storage::disk()->url($request->inv_url) : null,
            "invoice_sum" => $this->sum ?? 0
        ];
    }
}
