<?php

namespace Modules\Integrations\Http\Controllers\Telephony;

use App\Service\RequestBranch;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Illuminate\Support\Str;
use Modules\CompanyOffice\Services\CompanyRoles;
use Modules\Integrations\Entities\Beeline\BeelineTelephony;

class BeelineController extends Controller
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

        return $this->currentBranch->company->beelineTelephony ?: \response()->json(false);
    }

    function createAccount(Request $request)
    {
        if ($this->currentBranch->company->beelineTelephony) {
            $this->currentBranch->company->beelineTelephony->update([
                'api_token' => $request->input('api_token')
            ]);
            return \response()->json();
        }
        $model = $this->currentBranch->company->beelineTelephony()->save(new BeelineTelephony([
            'api_token' => $request->input('api_token'),
            'url_token' => Str::random(16)
        ]));
        $model->registerSubscription();

        return $model;
    }

    function removeAccount()
    {
        return $this->currentBranch->company->beelineTelephony()->delete();
    }
}
