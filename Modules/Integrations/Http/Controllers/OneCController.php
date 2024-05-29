<?php

namespace Modules\Integrations\Http\Controllers;

use App\Service\RequestBranch;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Illuminate\Support\Str;
use Modules\CompanyOffice\Services\CompanyRoles;
use Modules\Integrations\Services\OneC\OneCService;

class OneCController extends Controller
{

    private $currentBranch;

    public function __construct(Request $request, RequestBranch $companyBranch)
    {
        $this->currentBranch = $companyBranch->companyBranch;
        $block = $this->currentBranch->getBlockName(CompanyRoles::BRANCH_CLIENTS);
        $this->middleware("accessCheck:{$block}," . CompanyRoles::ACTION_CREATE)->only(
            [
                'getConnector',
                'connect',
            ]);


    }

    function getConnector($id)
    {
        $service = new OneCService($this->currentBranch);
        return $service->getConnection();
    }

    function checkConnection($id)
    {
        $service = new OneCService($this->currentBranch);
        $data = $service->checkConnection();
        return response()->json($data['data'], $data['code']);
    }

    function connect(Request $request, $id)
    {
        $request->validate([
            'login' => 'required|string|max:255',
            'password' => 'nullable|string|max:255',
            'delivery_vendor_code' => 'nullable|string|max:255',
            'default_vendor_code' => 'nullable|string|max:255',
            'base_login' => (toBool($request->input('base_auth')) ? 'required' : 'nullable') . '|string|max:255',
            'base_password' => (toBool($request->input('base_auth')) ? 'required' : 'nullable') . '|string|max:255',
            'url' => 'required|url',
        ]);
        $service = new OneCService($this->currentBranch);

        return $service->addConnection($request->input('login'),
            $request->input('password'),
            $request->input('url'),
            $request->input('delivery_vendor_code'),
            $request->input('default_vendor_code'),
            $request->input('pledge_vendor_code'),

            $request->only([
           'base_login',
           'base_password',
           'base_auth',
        ]));
    }

    function searchByInn(Request $request)
    {
        $service = new OneCService($this->currentBranch);
        return $service->client ? $service->getContractNumber($request->input('inn')) : response()->noContent();
    }
}
