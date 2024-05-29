<?php

namespace Modules\Integrations\Http\Controllers\Telephony;

use App\Service\RequestBranch;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Illuminate\Support\Str;
use Modules\CompanyOffice\Services\CompanyRoles;
use Modules\Integrations\Entities\Uis\UisTelephony;

class UisController extends Controller
{
    private $currentBranch;

    public function __construct(Request $request, RequestBranch $companyBranch)
    {
        $this->currentBranch = $companyBranch->companyBranch;


        $block = $this->currentBranch->getBlockName(CompanyRoles::BRANCH_DASHBOARD);
        $this->middleware("accessCheck:{$block}," . CompanyRoles::ACTION_SHOW)->only(
            [
                'getAccount',
                'createAccount',
                'removeAccount',
            ]);


    }

    function getAccount()
    {

        return $this->currentBranch->company->uisTelephony ?: \response()->json(false);
    }

    function createAccount(Request $request)
    {
        if ($this->currentBranch->company->uisTelephony) {
            $this->currentBranch->company->uisTelephony->update([
                'token' => $request->input('token')
            ]);
            return \response()->json();
        }
        return $this->currentBranch->company->uisTelephony()->save(new UisTelephony([
            'token' => $request->input('token')
        ]));
    }

    function removeAccount()
    {
        return $this->currentBranch->company->uisTelephony()->delete();
    }

}
