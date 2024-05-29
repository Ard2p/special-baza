<?php

namespace Modules\AdminOffice\Transformers;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Auth;

class EditUser extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param \Illuminate\Http\Request
     * @return array
     */
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'email' => $this->email,
            'phone' => $this->phone,
            'branches' => $this->branches,
            'contact_person' => $this->contact_person,
            'domains' => $this->adminDomainAccess()->pluck('domains.id'),
            'contractor_balance' => $this->contractor_balance,
            'roles' => $this->roles,
            'order_management' => $this->order_management,
            'email_confirm' => $this->email_confirm,
            'phone_confirm' => $this->phone_confirm,
            'is_regional_representative' => $this->is_regional_representative,
            'native_region_id' => $this->native_region_id,
            'country_id' => $this->country_id,
            'native_city_id' => $this->native_city_id,
            'regional_representative_id' => $this->regional_representative_id,
            'contractor_alias' => $this->contractor_alias,
            'contractor_alias_enable' => $this->contractor_alias_enable,
            'passed_moderation' => $this->passed_moderation,
            'last_activity' =>  (string) $this->last_activity,
            $this->mergeWhen($this->dashboard_access, [
                'dashboard_access' => $this->dashboard_access
            ]),
            $this->mergeWhen(Auth::user()->isSuperAdmin(), [
                'ya_call' => $this->ya_call
            ]),


        ];
    }
}
