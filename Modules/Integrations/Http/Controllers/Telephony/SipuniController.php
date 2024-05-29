<?php

namespace Modules\Integrations\Http\Controllers\Telephony;

use App\Service\RequestBranch;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Illuminate\Support\Str;
use Modules\CompanyOffice\Services\CompanyRoles;
use Modules\Integrations\Entities\SipuniTelephonyAccount;
use Modules\Integrations\Entities\Uis\sipuniTelephony;

class SipuniController extends Controller
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

        return $this->currentBranch->sipuniTelephony ?: \response()->json(false);
    }

    function createAccount(Request $request)
    {
        if ($this->currentBranch->sipuniTelephony) {
            $this->currentBranch->sipuniTelephony->update([
                'token' => $request->input('token'),
                'settings' => (object) $request->collect('settings')->filter(fn($item) => !!$item)->toArray()
            ]);
            return \response()->json();
        }
        return $this->currentBranch->sipuniTelephony()->save(new SipuniTelephonyAccount([
            'token' => $request->input('token'),
            'settings' => $request->collect('settings')->filter->toArray()
        ]));
    }

    function removeAccount()
    {
        return $this->currentBranch->company->sipuniTelephony()->delete();
    }

}
