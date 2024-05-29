<?php

namespace Modules\ContractorOffice\Http\Controllers\System;

use App\Service\RequestBranch;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Modules\CompanyOffice\Services\CompanyRoles;
use Modules\ContractorOffice\Entities\System\TariffUnitCompare;
use Modules\ContractorOffice\Services\Tariffs\TimeCalculation;

class TariffUnitsCompareController extends Controller
{

    private $companyBranch;

    public function __construct(Request $request, RequestBranch $companyBranch)
    {
        $this->companyBranch = $companyBranch->companyBranch;
        $block = $this->companyBranch->getBlockName(CompanyRoles::BRANCH_VEHICLES);
        $this->middleware("accessCheck:{$block}," . CompanyRoles::ACTION_SHOW)->only([
            'index',
            'store',
            'destroy',
            'update',
            'show',
        ]);
    }

    /**
     * Display a listing of the resource.
     * @return Response
     */
    public function index()
    {
        $units = TariffUnitCompare::query()->forBranch()->get();

        return  $units;
    }

    /**
     * Store a newly created resource in storage.
     * @param Request $request
     * @return Response
     */
    public function store(Request $request)
    {
        $request->validate([
            '*.name' => 'required|string|max:255',
            '*.amount' => 'required|int|min:1',
            '*.type' => 'required|in:hour,shift',
        ]);


        foreach ($request->all() as $item)
        {
            $fields = [
                'name' => $item['name'],
                'amount' => $item['amount'],
                'type' => $item['type'],
                'is_month' => $item['is_month'] ?? false,
                'company_branch_id' => $this->companyBranch->id,
            ];


            if(!empty($item['id'])) {
                $unit = TariffUnitCompare::query()->forBranch()->findOrFail($item['id']);
                $unit->update($fields);
            }else {

                TariffUnitCompare::create($fields);

            }

        }

        return  response()->json();
    }


    /**
     * Remove the specified resource from storage.
     * @param int $id
     * @return Response
     */
    public function destroy($id)
    {
        TariffUnitCompare::query()->forBranch()
            ->where('amount', '!=', 1)
            ->whereNotIn('type', [TimeCalculation::TIME_TYPE_HOUR, TimeCalculation::TIME_TYPE_SHIFT])
            ->where('id', $id)
            ->delete();
    }
}
