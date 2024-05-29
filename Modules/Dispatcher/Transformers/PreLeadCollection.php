<?php

namespace Modules\Dispatcher\Transformers;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Resources\Json\ResourceCollection;

class PreLeadCollection extends JsonResource
{
    /**
     * Transform the resource collection into an array.
     *
     * @param  \Illuminate\Http\Request
     * @return array
     */
    public function toArray($request)
    {


        return [
            'id' => $this->id,
            'audits' => $this->audits->load('user'),
            'internal_number' => $this->internal_number,
            'name' => $this->name,
            'contact_person' => $this->contact_person,
            'contacts' => $this->contacts,
            'status' => $this->status,
            'phone' => $this->phone,
            'email' => $this->email,
            'address' => $this->address,
            'date_from' => $this->date_from ? $this->date_from->format('Y-m-d') : '',
            'time_from' => $this->date_from ? $this->date_from->format('H:i') : '',
            'order_duration' => $this->order_duration,
            'order_type' => $this->order_type,
            'comment' => $this->comment,
            'lead_id' => $this->lead_id,
            'coordinates' => $this->coordinates,
            'rejected' => $this->rejected,
            'reject_type' => $this->reject_type,
            'reject_type_reason' => $this->rejectType,
            'created_at' => (string)$this->created_at,
            'customer_id' => $this->customer_id,
            'creator_id' => $this->creator_id,
            'manager' => $this->manager,
            'customer' => $this->customer,
            'positions' => collect($this->positions->map(function ($item) {
                if($item->category) {
                    $item->category->localization();
                }
                $item->attributes = $item->attributes->map(function ($item) {
                    $item->value = $item->pivot->value;
                    return $item;
                });
                return $item;
            })->toArray())->map(function ($item) {
                $item['attributes'] = collect($item['attributes'] )->mapWithKeys(function ($item) {
                    return [$item['id'] => $item['pivot']['value']];
                });
                return $item;
            }),

        ];
    }
}
