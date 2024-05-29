<?php

namespace Modules\RestApi\Transformers;

use App\Service\RequestBranch;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\AdminOffice\Services\RoleService;
use Modules\Dispatcher\Entities\Customer;

class User extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param \Illuminate\Http\Request
     * @return array
     */
    public function toArray($request)
    {

        $branch = app(RequestBranch::class)->companyBranch;

        $company = app(RequestBranch::class)->company;

        if($branch) {
            $userBranch = $this->branches->where('id', $branch->id)->first();
            $base = $userBranch ? $userBranch->pivot->machinery_base_id : null;
        }
        /** @var Collection $customers */
        $customers = Customer::query()->whereHas('corpUsers', function (Builder $q) {
            $q->where('users.id', $this->id);
        })->get();
        $customers = $customers->map(function ($item) {

            return $item->only(['id', 'company_name', 'corp_cabinet_url', 'company_branch_id', 'company_id']);
        })->toArray();

        return [
            'active'                     => $this->active,
            'avatar'                     => $this->avatar,
            'city'                       => $this->city,
            'address'                    => $this->address,
            'contact_person'             => $this->contact_person,
//            'my_regional_representative' => ($this->regional_representative
//                ? [
//                    'name'  => $this->regional_representative->rp_name,
//                    'phone' => $this->regional_representative->phone,
//                ]
//                : ''),
            'customer_access'            => $customers,
            'contractor_alias'           => $this->contractor_alias,
            'contractor_alias_enable'    => $this->contractor_alias_enable,
          //  'contractor_balance'         => $this->contractor_balance,
            'country_id'                 => $this->country_id,
            'created_at'                 => (string)$this->created_at,
            'email'                      => $this->email,
            'email_confirm'              => $this->email_confirm,
            'id'                         => $this->id,
            'name'                       => $this->name,
            'native_city_id'             => $this->native_city_id,
            'native_region_id'           => $this->native_region_id,
            'phone'                      => $this->phone,
            'phone_confirm'              => $this->phone_confirm,
            'promo_code'                 => $this->promo_code,
            'public_page'                => $this->public_page,
            'machinery_base_id'                => $base ?? null,
            'region'                     => $this->region,
            'country'                    => $this->country,
            $this->mergeWhen(!$branch, [
                'company_roles' => $company
                    ? $this->getCompanyRoles($company->id)
                    : [],
            ]),

            'branch_roles'          => $branch
                ? $this->getBranchRoles($branch->id)
                : [],
            'branch_dashboard_info' => $branch
                ? $branch->getDashboardInfo()
                : null,
            //'wialon' => $this->wialonAccount,
           // 'is_contractor'         => $this->hasVehicles(),
            $this->mergeWhen($this->isSuperAdmin(), [
                'is_admin' => 1,
            ]),

            'permissions' => $branch
                ? $this->permissions()->where('name', 'like', ($branch
                    ? "{$branch->id}_branch%"
                    : ''))->pluck('name')
                : [],

            'branches'      => $this->branches->map->only(['id', 'name', 'company_id', 'requisite','avito_partner','commission']),
            $this->mergeWhen($this->isRegionalRepresentative() || $this->isSuperAdmin(), [
                'is_rp_user' => 1,
            ]),
            $this->mergeWhen($this->isContentAdmin(), [
                'is_content_admin' => 1,
            ]),
            $this->mergeWhen($this->isSuperAdmin() || $this->dashboard_access, [
                'domains' => $this->adminDomainAccess,
            ]),
            'access_blocks' =>
                $this->isSuperAdmin()
                    ? RoleService::getAdminPermissions()
                    : $this->getAllPermissions(),
            $this->mergeWhen($this->ya_call && ($this->ya_call
                    ? $this->ya_call->enable
                    : false), [
                'ya_call' => $this->ya_call,
            ]),

        ];
    }
}
