<?php

namespace Modules\CompanyOffice\Http\Controllers;

use App\Service\RequestBranch;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Modules\CompanyOffice\Entities\Company\CompanyBranch;
use Modules\CompanyOffice\Entities\Company\Documents\CommercialOffer;
use Modules\CompanyOffice\Services\CompanyRoles;

class CommercialOffersController extends Controller
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
        return $this->currentBranch->commercialOffers;
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
            'number' => 'required|string|max:255',
            'url' => 'required|string|max:255',
            'default_text' => 'nullable|string|max:9999',
        ]);

        $this->currentBranch->commercialOffers()->save(new CommercialOffer(
            [
                'name' => $request->input('name'),
                'number' => $request->input('number'),
                'url' => $request->input('url'),
                'default_text' => $request->input('default_text'),
            ]
        ));

        return response()->json();
    }


    /**
     * Update the specified resource in storage.
     * @param Request $request
     * @param int $id
     * @return Response
     */
    public function update(Request $request, $id, $commercialId)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'number' => 'required|string|max:255',
            'url' => 'required|string|max:255',
            'default_text' => 'nullable|string|max:9999',
        ]);

        $commercialService = $this->currentBranch->commercialOffers()->findOrFail($commercialId);

        $commercialService->update(
            [
                'name' => $request->input('name'),
                'number' => $request->input('number'),
                'url' => $request->input('url'),
                'default_text' => $request->input('default_text'),
            ]
        );

        return response()->json();
    }

    /**
     * Remove the specified resource from storage.
     * @param int $id
     * @return Response
     */
    public function destroy($id, $commercialId)
    {
         $this->currentBranch->commercialOffers()->findOrFail($commercialId)->delete();
    }
}
