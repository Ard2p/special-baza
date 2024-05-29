<?php

namespace Modules\Integrations\Http\Controllers\Telephony;

use App\Service\RequestBranch;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Illuminate\Support\Str;
use Modules\CompanyOffice\Services\CompanyRoles;
use Modules\Integrations\Entities\MegafonTelephony\MegafonAccount;
use Modules\Integrations\Entities\Telpehony\TelephonyCallHistory;
use Modules\RestApi\Transformers\User;

class MegafonController extends Controller
{

    private $currentBranch;

    public function __construct(Request $request, RequestBranch $companyBranch)
    {
        $this->currentBranch = $companyBranch->companyBranch;

        if(!Str::contains($request->route()->getActionName(), 'postback')) {
            $block = $this->currentBranch->getBlockName(CompanyRoles::BRANCH_DASHBOARD);
            $this->middleware("accessCheck:{$block}," . CompanyRoles::ACTION_SHOW)->only(
                [
                    'getAccount',
                    'createAccount',
                    'removeAccount',
                ]);
        }

    }

    function getAccount()
    {

        return $this->currentBranch->company->megafonTelephony ?: \response()->json(false);
    }

    function createAccount(Request $request)
    {
        if($this->currentBranch->company->megafonTelephony){
            return \response()->json();
        }
        return $this->currentBranch->company->megafonTelephony()->save(new MegafonAccount([
            'token' => Str::random(16)
        ]));
    }

    function removeAccount()
    {
        return $this->currentBranch->company->megafonTelephony()->delete();
    }

    function postback(Request $request)
    {
        $account = MegafonAccount::query()
            ->where('token', $request->input('crm_token'))
            ->firstOrFail();

        TelephonyCallHistory::createOrUpdate($account, $request->all());
        return response()->json();
    }






}
