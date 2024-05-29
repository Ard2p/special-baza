<?php


namespace Modules\CompanyOffice\Services;


use App\Service\RequestBranch;
use Modules\CompanyOffice\Entities\Company\CompanyBranch;

trait BelongsToCompanyBranch
{

    function scopeForBranch($q, $branch_id = null)
    {
        $branch_id = $branch_id ?: app(RequestBranch::class)->companyBranch->id;
        return $q->where($this->table.'.company_branch_id', $branch_id);
    }

    function company_branch()
    {
        return $this->belongsTo(CompanyBranch::class);
    }


    function scopeForCompany($q, $company_id = null)
    {
        $company_id = $company_id ?: app(RequestBranch::class)->company->id;

        return $q->whereHas('company_branch', function ($q) use ($company_id){
            $q->where('company_id', $company_id);
        });
    }


}
