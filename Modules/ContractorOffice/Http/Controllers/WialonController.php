<?php

namespace Modules\ContractorOffice\Http\Controllers;

use App\Machinery;
use App\Service\RequestBranch;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Modules\CompanyOffice\Entities\Company\CompanyBranch;
use Modules\CompanyOffice\Services\CompanyRoles;
use Modules\Integrations\Entities\Wialon;
use Modules\Integrations\Entities\WialonVehicle;

class WialonController extends Controller
{

    /** @var CompanyBranch */
    private $currentBranch;

    public function __construct(Request $request, RequestBranch $companyBranch)
    {
        $this->currentBranch = $companyBranch->companyBranch;


        $block = $this->currentBranch->getBlockName(CompanyRoles::BRANCH_VEHICLES);
        $this->middleware("accessCheck:{$block}," . CompanyRoles::ACTION_SHOW)->only(
            [
                'checkConnection',
                'getConnection',
                'getVehicles',

            ]);
    }
    function checkConnection(Request $request)
    {
        $errors = Validator::make($request->all(), [
            'host' => 'required|url',
            'login_url' => 'required|url',
            'login' => 'required|string|max:255|min:2',
            'password' => 'required|string|max:255|min:4',
        ])->errors()->getMessages();
        if ($errors) {
            return response()->json($errors, 400);
        }
        \DB::beginTransaction();
        $account = $this->currentBranch->wialonAccount ?: new Wialon();

        $account->fill($request->only([
            'host', 'login_url', 'login', 'password'
        ]));

        $account->creator_id = Auth::id();
        $account->company_branch_id = $this->currentBranch->id;
        $account->save();

        \DB::commit();
        try {
           $account->getToken();
            $account->loadVehicles();
        } catch (\Exception $exception) {
            Log::info($exception->getMessage());
            return response()->json([], 400);
        }


        return response()->json($account);
    }


    function getConnection()
    {
      return  $this->currentBranch->wialonAccount ?: false;
    }


    function getVehicles(Request $request)
    {
        $vehicles = WialonVehicle::query()->forBranch();

        if($request->filled('get_for_vehicle')){

            $vehicles->whereNull('machinery_id');

            $vehicle_id = $request->input('get_for_vehicle');

            if(Machinery::forBranch()->find($vehicle_id)){
                $vehicles->orWhere('machinery_id', $vehicle_id);
            }

        }

        $vehicles = $vehicles->get();

        return $vehicles;
    }


}
