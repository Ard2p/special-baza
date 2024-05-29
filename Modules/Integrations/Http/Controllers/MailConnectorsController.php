<?php

namespace Modules\Integrations\Http\Controllers;

use App\Service\RequestBranch;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Illuminate\Support\Str;
use Modules\CompanyOffice\Entities\Company\CompanyBranch;
use Modules\CompanyOffice\Services\CompanyRoles;
use Modules\Integrations\Entities\Mails\MailConnector;

class MailConnectorsController extends Controller
{
    /** @var CompanyBranch */
    private $currentBranch;

    public function __construct(Request $request, RequestBranch $companyBranch)
    {
        $this->currentBranch = $companyBranch->companyBranch;
        $block = $this->currentBranch->getBlockName(CompanyRoles::BRANCH_DASHBOARD);
        $this->middleware("accessCheck:{$block}," . CompanyRoles::ACTION_SHOW)->only(
            [
                'getConnector',
            ]);

        $this->middleware("accessCheck:{$block}," . CompanyRoles::ACTION_CREATE)->only(
            [
                'create',
                'update',
                'delete',
            ]);

    }

    function getConnector(Request $request)
    {
        return $request->input('type') === 'user' ? \Auth::user()->mailConnector: $this->currentBranch->mailConnector;
    }
    function create(Request $request)
    {
        $request->validate([
            'email' => 'required|email|max:255',
            'password' => 'required|string|max:255',
            'server' => 'nullable|string|max:30'
        ]);

        $connector = MailConnector::createConnector(
            $request->input('email'),
            $request->input('password'),
            $request->input('server'),
            [
              'server' => $request->input('smtp_server') ,
              'port' => $request->input('smtp_port') ,
            ],
            ($request->input('type') === 'user' ? \Auth::user() : $this->currentBranch),
            $this->currentBranch
        );

        return response()->json($connector->token);
    }

    function update(Request $request)
    {
        $request->validate([
            'email' => 'required|email|max:255',
            'password' => 'nullable|string|max:255',
            'server' => 'nullable|string|max:30'
        ]);

        $owner =  ($request->input('type') === 'user' ? \Auth::user() : $this->currentBranch);
        $connector = $owner->mailConnector->updateConnector(
            $request->input('email'),
            $request->input('password'),
            $request->input('server'),
            [
                'server' => $request->input('smtp_server'),
                'port' => $request->input('smtp_port'),
            ]
        );


        return response()->json($connector);
    }

    function delete(Request $request)
    {
        $owner =  ($request->input('type') === 'user' ? \Auth::user() : $this->currentBranch);

        $connector = $owner->mailConnector->deleteConnector();

        return response()->json();
    }


}
