<?php

namespace Modules\PartsWarehouse\Http\Controllers;

use App\Service\RequestBranch;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Modules\CompanyOffice\Services\CompanyRoles;
use Modules\Integrations\Services\OneC\OneCService;
use Modules\PartsWarehouse\Entities\Stock\Stock;
use Modules\PartsWarehouse\Http\Requests\StockRequest;
use Modules\PartsWarehouse\Services\StockService;

class StocksController extends Controller
{

    private $companyBranch;

    public function __construct(Request $request, RequestBranch $companyBranch)
    {
        $this->companyBranch = $companyBranch->companyBranch;
        $block = $this->companyBranch->getBlockName(CompanyRoles::BRANCH_PROPOSALS);
        $this->middleware("accessCheck:{$block}," . CompanyRoles::ACTION_SHOW)->only([
            'index', 'show',
        ]);

        $this->middleware("accessCheck:{$block}," . CompanyRoles::ACTION_CREATE)->only([
            'store',
            'update',
        ]);

        $this->middleware("accessCheck:{$block}," . CompanyRoles::ACTION_DELETE)->only(['destroy']);
    }


    public function index()
    {
        $stocks = Stock::query()->forBranch()->whereNull('parent_id')->get();

        if($this->companyBranch->OneCConnection) {
              $service = new OneCService($this->companyBranch);

            $onecStocks = $service->getEntityInfo(Stock::class, '');
        }

        return [
            'stocks' => $stocks,
            'onec' => $onecStocks ?? []
        ];
    }


    /**
     * Store a newly created resource in storage.
     * @param StockRequest $request
     * @return void
     */
    public function store(StockRequest $request)
    {
        $service = new StockService($this->companyBranch);
        $service->setData($request->all());
        $stock = $service->createStock();

        return response()->json($stock);
    }



    /**
     * Show the specified resource.
     * @param int $id
     * @return Response
     */
    public function show($id)
    {
        return view('partswarehouse::show');
    }

    /**
     * Update the specified resource in storage.
     * @param Request $request
     * @param int $id
     * @return Response
     */
    public function update(StockRequest $request, $id)
    {
        $service = new StockService($this->companyBranch);
        $service->setData($request->all());
        $service->updateStock($id);
        return response()->json();
    }

    /**
     * Remove the specified resource from storage.
     * @param int $id
     * @return Response
     */
    public function destroy($id)
    {
        $stock = Stock::query()->forBranch($this->companyBranch->id)->findOrFail($id);

        if($stock->getItemsCountRecursive() === 0) {
            $stock->delete();
            return  response()->json();
        }

        return response()->json(['errors' => ['Невозможно удалить. На складе есть запчасти!']], 400);
    }
}
