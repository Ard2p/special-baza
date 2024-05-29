<?php


namespace Modules\CompanyOffice\Services;


use App\Service\RequestBranch;
use Modules\CompanyOffice\Entities\Company;
use Modules\CompanyOffice\Entities\Company\CompanyBranch;

trait BelongsToCompany
{

    function scopeForCompany($q, $company = null)
    {
        $company = $company ?: app(RequestBranch::class)->company->id;
        return $q->where('company_id', $company);
    }

    function company()
    {
        return $this->belongsTo(Company::class);
    }


}