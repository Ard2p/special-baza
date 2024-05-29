<?php

namespace Modules\CompanyOffice\Http\Controllers;

use App\Service\RequestBranch;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Modules\CompanyOffice\Entities\Company\CompanyBranch;
use Modules\CompanyOffice\Entities\Company\Documents\CommercialOffer;
use Modules\CompanyOffice\Entities\Expenditure;
use Modules\CompanyOffice\Services\CompanyRoles;

class ExpenditureController extends Controller
{

    /** @var CompanyBranch */
    private $currentBranch;

    public function __construct(Request $request, RequestBranch $companyBranch)
    {
        $this->currentBranch = $companyBranch->companyBranch;


        $block = $this->currentBranch->getBlockName(CompanyRoles::BRANCH_DASHBOARD);
        $this->middleware("accessCheck:{$block}," . CompanyRoles::ACTION_SHOW)->only(
            [
                'index',
                'show',
            ]);
        $this->middleware("accessCheck:{$block}," . CompanyRoles::ACTION_UPDATE)->only(
            [
                'store',
                'update',
                'destroy',

            ]);

    }
    /**
     * Display a listing of the resource.
     * @return Response
     */
    public function index()
    {
        return $this->currentBranch->expenditures;
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
        ]);

        $this->currentBranch->expenditures()->save(new Expenditure(
            [
                'name' => $request->input('name'),
            ]
        ));

        return response()->json();
    }

    /**
     * Show the specified resource.
     * @param int $id
     * @return Response
     */
    public function show($id)
    {
        return view('companyoffice::show');
    }


    /**
     * Update the specified resource in storage.
     * @param Request $request
     * @param int $id
     * @return Response
     */
    public function update(Request $request, $id, $expenditureId)
    {
        $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $commercialService = $this->currentBranch->expenditures()->findOrFail($expenditureId);

        $commercialService->update(
            [
                'name' => $request->input('name'),
            ]
        );

        return response()->json();
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
