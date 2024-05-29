<?php

namespace Modules\ContractorOffice\Http\Controllers\MachineryShop;

use App\Service\RequestBranch;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Illuminate\Support\Str;
use Modules\CompanyOffice\Services\CompanyRoles;
use Modules\ContractorOffice\Entities\Vehicle\Shop\MachinerySale;
use Modules\ContractorOffice\Entities\Vehicle\Shop\MachinerySaleRequest;
use Modules\ContractorOffice\Transformers\Vehicle\Shop\MachinerySaleResource;

class SalesController extends Controller
{

    private $companyBranch;

    public function __construct(Request $request, RequestBranch $companyBranch)
    {
        $this->companyBranch = $companyBranch->companyBranch;

        $block = $this->companyBranch->getBlockName(CompanyRoles::BRANCH_PROPOSALS);

        $this->middleware("accessCheck:{$block}," . CompanyRoles::ACTION_SHOW)->only('index', 'show', 'getApplication', 'getContract', 'getDocuments');
        $this->middleware("accessCheck:{$block}," . CompanyRoles::ACTION_CREATE)->only(['store', 'update']);
        $this->middleware("accessCheck:{$block}," . CompanyRoles::ACTION_DELETE)->only(['destroy']);
    }

    /**
     * Display a listing of the resource.
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function index(Request $request)
    {
        /** @var Builder $saleRequests */
        $saleRequests = MachinerySale::query()->with('operations')->forBranch();

        return MachinerySaleResource::collection($saleRequests->orderBy('created_at', 'desc')->paginate($request->per_page ?: 15));
    }


    /**
     * Store a newly created resource in storage.
     * @param Request $request
     * @return Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Show the specified resource.
     * @param int $id
     * @return Response
     */
    public function show($id)
    {
        $sale = MachinerySale::query()->forBranch()->findOrFail($id);
        return MachinerySaleResource::make($sale);
    }

    /**
     * Update the specified resource in storage.
     * @param Request $request
     * @param int $id
     * @return Response
     */
    public function update(Request $request, $id)
    {
        //
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


    function getApplication(Request $request, $saleId, $applicationId)
    {
        /** @var MachinerySale $sale */
        $sale = MachinerySale::query()->forBranch()->findOrFail($saleId);

        return response()->json([
            'url' => $sale->generateApplication($applicationId)
        ]);
    }

    function getContract($id)
    {
        /** @var MachinerySale $sale */
        $sale = MachinerySale::query()->forBranch()->findOrFail($id);

        $url = $sale->getContractUrl();

        return $url ? response()->json([
            'url' => $url
        ])
            : response()->json([], 400);
    }

    function getDocuments($id)
    {
        $sale = MachinerySale::query()->forBranch()->findOrFail($id);

        return $sale->documents;
    }
}
