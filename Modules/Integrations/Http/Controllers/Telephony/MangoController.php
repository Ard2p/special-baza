<?php

namespace Modules\Integrations\Http\Controllers\Telephony;

use App\Service\RequestBranch;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\CompanyOffice\Services\CompanyRoles;
use Modules\Integrations\Entities\MangoTelephony;

class MangoController extends Controller
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

        return $this->currentBranch->mangoTelephony ?: \response()->json(false);
    }

    function createAccount(Request $request)
    {
        if ($this->currentBranch->mangoTelephony) {
            $this->currentBranch->mangoTelephony->update([
                'token' => $request->input('token'),
                'settings' => (object) $request->collect('settings')->filter(fn($item) => !!$item)->toArray()
            ]);
            return \response()->json();
        }
        return $this->currentBranch->mangoTelephony()->save(new MangoTelephony([
            'token' => $request->input('token'),
            'sign' => $request->input('sign'),
            'settings' => $request->collect('settings')->filter->toArray()
        ]));
    }

    function removeAccount()
    {
        return $this->currentBranch->company->mangoTelephony()->delete();
    }

}
