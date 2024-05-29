<?php

namespace Modules\Dispatcher\Http\Controllers;

use App\Service\RequestBranch;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Modules\CompanyOffice\Services\CompanyRoles;
use Modules\Dispatcher\Entities\Lead;
use Modules\Orders\Entities\Order;

class ContractsController extends Controller
{

    private $companyBranch;

    public function __construct(Request $request, RequestBranch $companyBranch)
    {
        $this->companyBranch = $companyBranch->companyBranch;
        $block = $this->companyBranch->getBlockName(CompanyRoles::BRANCH_PROPOSALS);
        $this->middleware("accessCheck:{$block}," . CompanyRoles::ACTION_SHOW)->only('getContract');
        $this->middleware("accessCheck:{$block}," . CompanyRoles::ACTION_CREATE)->only(['addContract']);
        $this->middleware("accessCheck:{$block}," . CompanyRoles::ACTION_DELETE)->only(['deleteContract']);

    }
    function getContract($lead_id)
    {
        $lead = Lead::forBranch()->findOrFail($lead_id);

        return $lead->contract ?: \response()->json(false);

    }

    function formContract(Request $request, $lead_id)
    {
        $lead = Lead::forBranch()->findOrFail($lead_id);


        if(!$lead->contract) {
            return response()->json([], 400);
        }
        $url = $lead->contract->getLeadContractUrl($request->input('document_pack_id'));

        return $url ? response()->json([
            'url' => $url
        ])
            : response()->json([], 400);
    }

    function addContract(Request $request, $id)
    {
        $lead = Lead::forBranch()->findOrFail($id);

        $request->validate([
            'name' => 'required|string|max:255',
            'doc' => 'required|string|max:255',
        ]);
        $tmp_dir = config('app.upload_tmp_dir');

        $tmp_file_path = $request->input('doc');

        $exists = Storage::disk()->exists($request->input('doc'));

        if (!$exists || !Str::contains($tmp_file_path, $tmp_dir)) {
            return response()->json(['doc' => ['Файл не найден. Попробуйте еще раз.']], 400);
        }


        return  $lead->addContract($request->input('name'), $tmp_file_path);
    }

    function deleteContract($lead_id)
    {
        $lead = Lead::forBranch()->findOrFail($lead_id);

        return $lead->contract->remove();
    }


}
