<?php

namespace Modules\CompanyOffice\Http\Controllers\Insurance;

use App\Service\RequestBranch;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\CompanyOffice\Entities\Company\CompanyBranch;
use Modules\CompanyOffice\Entities\Company\InsTariffSetting;
use Modules\CompanyOffice\Services\CompanyRoles;

class InsTariffSettingController extends Controller
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
                'update',
                'save'
            ]);
    }

    public function save(Request $request)
    {
        $tariffSettings = collect($request->all());
        $idsNotToDelete = $tariffSettings->pluck('id')->toArray();
        InsTariffSetting::query()
            ->where('company_branch_id', $this->currentBranch->id)
            ->whereNotIn('id', $idsNotToDelete)->delete();
        $this->currentBranch->ins_tariff_settings()->upsert($request->all(), 'id');
        return $this->currentBranch->ins_tariff_settings;
    }

    public function store(Request $request)
    {
        $ins_tariff_setting = InsTariffSetting::query()->create($request->all());
        return $ins_tariff_setting;
    }

    public function show(InsTariffSetting $ins_tariff_setting)
    {
        return $ins_tariff_setting;
    }

    public function update(Request $request, InsTariffSetting $ins_tariff_setting)
    {
        $ins_tariff_setting->update($request->all());

        return $ins_tariff_setting;
    }

    public function delete(InsTariffSetting $ins_tariff_setting)
    {
        $ins_tariff_setting->delete();

        return null;
    }
}
