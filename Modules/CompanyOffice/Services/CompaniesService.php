<?php


namespace Modules\CompanyOffice\Services;


use App\User;
use Modules\CompanyOffice\Entities\Company;
use Modules\Profiles\Entities\UserNotification;

class CompaniesService
{

    private $company;

    public function __construct(Company $company)
    {

        $this->company = $company;

    }

    static function createCompany(User $creator, $domain_id, $name = null, $alais = null)
    {

        $company = Company::create([
            'name' => ($name ?: 'company'),
            'domain_id' => $domain_id,
            'creator_id' => $creator->id,
            'alias' => $alais
        ]);

        return new self($company);
    }


    function createBranch($name = null, $region_id = null, $city_id = null, $creator_id = null, $alias = null)
    {
        $creator_id = $creator_id ?: $this->company->creator_id;

        /** @var Company\CompanyBranch $branch */
        $branch = Company\CompanyBranch::create([
            'name' => $name ?: $this->company->name,
            'region_id' => $region_id,
            'city_id' => $city_id,
            'company_id' => $this->company->id,
            'creator_id' => $creator_id,
            'alias' => $creator_id,
        ]);
        $branch->employees()->syncWithoutDetaching([
            $creator_id => [
                'role' => CompanyRoles::ROLE_ADMINISTRATOR
            ]
        ]);
        /** @var User $user */
        $user = User::query()->findOrFail($creator_id);

        CompanyRoles::syncRoleWithPermissions($user, $branch->id, CompanyRoles::ROLE_ADMINISTRATOR);

        return $branch;
    }

    function addUsersNotification($message, User $initiator = null, $type = UserNotification::TYPE_INFO, $link = null,  Company\CompanyBranch $companyBranch = null, $permissions = null)
    {
        $employees = User::query()->whereHas('branches', function ($q) use ($companyBranch) {

            if ($companyBranch) {
                $q->where($companyBranch->getTable() . '.id', $companyBranch->id);
            } else {
                $q->where('company_id', $this->company->id);
            }

        })->get();

        foreach ($employees as $employee) {

            $notification = UserNotification::addNotification($message, $employee, $type, $companyBranch, $link);
            if($employee->id ===  ($initiator->id ?? null)) {
                $notification->update([
                    'is_read' => true
                ]);
            }
        }

        return $this;
    }

    function getBranchSettings($branch_id, $settings_key = null)
    {
        $branch = $this->company->branches->where('id', $branch_id)->first();

        $settings = $branch->getSettings();

        switch ($settings_key) {
            case 'default_contract':
                return collect($settings)->only([
                    'default_contract_name',
                    'default_contract_url',
                    'default_service_contract_url',
                    'default_contract_prefix',
                    'documents_head_image',
                    'default_contract_postfix',

                    'default_machinery_sale_contract_name',
                    'default_machinery_sale_contract_prefix',
                    'default_machinery_sale_contract_postfix',
                ]);
            default:
                return $settings;
        }

    }

    function setBranchContractSettings($branch_id, $data)
    {
        $branch = $this->company->branches->where('id', $branch_id)->first();

        /** @var Company\CompanyBranchSettings $settings */
        $settings = $branch->getSettings();
        //logger(json_encode($data));
        foreach (Company\CompanyBranchSettings::DOCUMENT_TEMPLATES as $DOCUMENT_TEMPLATE) {
            $settings->setDefaultDocument($data[$DOCUMENT_TEMPLATE] ?? null, $DOCUMENT_TEMPLATE);
        }

        $settings->setDefaultDocumentImage($data['documents_head_image'] ?? null);

        $settings->update([
            'price_without_vat' => $data['price_without_vat'] ?? false,
            'contract_number_template' => $data['contract_number_template'],
            'default_contract_name' => $data['default_contract_name'],
            'default_contract_prefix' => $data['default_contract_prefix'] ?? '',
            'default_contract_postfix' => $data['default_contract_postfix'] ?? '',

            'default_machinery_sale_contract_name' => $data['default_machinery_sale_contract_name'] ?? '',
            'default_machinery_sale_contract_prefix' => $data['default_machinery_sale_contract_prefix'] ?? '',
            'default_machinery_sale_contract_postfix' => $data['default_machinery_sale_contract_postfix'] ?? '',

            'contract_service_number_template' => $data['contract_service_number_template'] ?? '',
            'machinery_document_mask' => $data['machinery_document_mask'] ?? '',
            'contract_service_default_contract_prefix' => $data['contract_service_default_contract_prefix'] ?? '',
            'contract_service_default_contract_postfix' => $data['contract_service_default_contract_postfix'] ?? '',
            'use_shift_settings' => $data['use_shift_settings'] ?? false,
            'shift_settings' => $data['shift_settings'] ?? null,
            'split_invoice_by_month' => $data['split_invoice_by_month'] ?? null,
        ]);

        return $this;
    }
}
