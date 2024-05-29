<?php

namespace Modules\Orders\Http\Controllers;

use App\Service\RequestBranch;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Illuminate\Support\Collection;
use Modules\CompanyOffice\Entities\Company\CompanyBranch;
use Modules\CompanyOffice\Services\CompanyRoles;
use Modules\ContractorOffice\Entities\Services\CustomService;
use Modules\Orders\Entities\Service\ServiceWork;

class ServiceWorksController extends Controller
{

    /** @var CompanyBranch */
    private $companyBranch;

    public function __construct(RequestBranch $currentBranch)
    {
        $this->companyBranch = $currentBranch->companyBranch;

        $block = $this->companyBranch->getBlockName(CompanyRoles::BRANCH_PAYMENTS);

        $this->middleware("accessCheck:{$block}," . CompanyRoles::ACTION_SHOW)->only([
            'index', 'show'
        ]);
        $this->middleware("accessCheck:{$block}," . CompanyRoles::ACTION_CREATE)->only(['store']);

    }

    /**
     * Display a listing of the resource.
     * @return Response
     */
    public function index()
    {
        return CustomService::query()->forBranch()->where('is_for_service', 1)->get();
    }

    /**
     * Store a newly created resource in storage.
     * @param Request $request
     * @return Response
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'price' => 'required|numeric|min:0',
        ]);

        $serviceWork = ServiceWork::create([
            'name' => $request->input('name'),
            'price' => $request->input('price'),
            'company_branch_id' => $this->companyBranch->id
        ]);

        return response()->json($serviceWork);
    }



    /**
     * Update the specified resource in storage.
     * @param Request $request
     * @param int $id
     * @return Response
     */
    public function update(Request $request, $id)
    {

        $serviceWork = ServiceWork::forBanch()->findOrFail($id);

        $serviceWork->update([
            'name' => $request->input('name'),
            'price' => $request->input('price'),
            'company_branch_id' => $this->companyBranch->id
        ]);
    }

    /**
     * Remove the specified resource from storage.
     * @param int $id
     * @return Response
     */
    public function destroy($id)
    {
        //
    }
}
