<?php

namespace App\Service;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Modules\CompanyOffice\Entities\Company;
use Modules\RestApi\Entities\Domain;

class RequestBranch
{
    public ?Domain $domain = null;
    public ?Company $company = null;
    public ?Company\CompanyBranch $companyBranch = null;
    public Request $request;

    public function __construct(\Closure $request)
    {
        $this->request = $request();
        if ($this->request->header('company')) {
            $this->company =
                Company::query()->with('branches')->where('alias', $this->request->header('company'))->firstOrFail();

            if ($this->request->header('branch')) {
                $this->companyBranch = $this->company->branches->where('id', $this->request->header('branch'))->first();
                if (!$this->companyBranch) {
                    abort(404);
                }
            }
        }
    }

    function getDomain($key = null)
    {
        $this->domain = $this->domain ?:  Cache::remember(
            'domain_'.$this->request->header('domain', 'ru'),
            999,
            fn() => Domain::whereAlias($this->request->header('domain', 'ru'))->first());

        return $key ? $this->domain->{$key} : $this->domain;
    }
}