<?php

namespace Modules\CompanyOffice\Http\Controllers\Insurance;

use App\Service\RequestBranch;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\CompanyOffice\Entities\Company\CompanyBranch;
use Modules\CompanyOffice\Entities\Company\InsSetting;
use Modules\CompanyOffice\Services\CompanyRoles;

class InsSettingController extends Controller
{

    /** @var CompanyBranch */
    private $currentBranch;

    public function __construct(RequestBranch $companyBranch)
    {
        $this->currentBranch = $companyBranch->companyBranch;
        $block = $this->currentBranch->getBlockName(CompanyRoles::BRANCH_DASHBOARD);
        $this->middleware("accessCheck:{$block},".CompanyRoles::ACTION_SHOW)->only(
            [
                'show',
                'store',
                'update'
            ]);
    }

    public function store(Request $request)
    {
        $data = $request->all();
        $data['company_branch_id'] = $this->currentBranch->id;
        $ins_setting = InsSetting::query()->create($data);
        return $ins_setting;
    }

    public function get()
    {
        return $this->currentBranch->ins_setting;
    }

    public function update(Request $request, InsSetting $ins_setting)
    {
        $ins_setting->update($request->all());

        return $ins_setting;
    }
}
