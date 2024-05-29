<?php

namespace Modules\CompanyOffice\Http\Controllers\Requisites;

use App\Service\RequestBranch;
use App\User\IndividualRequisite;
use http\Exception\InvalidArgumentException;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Modules\CompanyOffice\Services\CompanyRoles;
use Modules\Dispatcher\Http\Requests\IndividualRequisiteRequest;

class IndividualController extends Controller
{

    private $companyBranch;

    public function __construct(Request $request, RequestBranch $companyBranch)
    {
        $this->companyBranch = $companyBranch->companyBranch;

        $block = $this->companyBranch->getBlockName(CompanyRoles::BRANCH_PAYMENTS);

        $this->middleware("accessCheck:{$block}," . CompanyRoles::ACTION_SHOW)->only('index');
        $this->middleware("accessCheck:{$block}," . CompanyRoles::ACTION_CREATE)->only(['store', 'update']);
        $this->middleware("accessCheck:{$block}," . CompanyRoles::ACTION_DELETE)->only(['destroy']);
    }

    /**
     * Display a listing of the resource.
     * @return Response
     */
    public function index(Request $request)
    {
        return $this->companyBranch->individual_requisites;
    }

    /**
     * Store a newly created resource in storage.
     * @param Request $request
     * @return Response
     */
    public function store(IndividualRequisiteRequest $request)
    {
        /*if($this->companyBranch->hasRequisite) {
            return  response()->json([
                'message' => 'Ошибка. Реквизиты уже заполнены.'
            ], 400);
        }*/
        $data = $request->all();

        $user = $request->user('api');

        $data['creator_id'] = $user->id;
        $data['company_branch_id'] = $this->companyBranch->id;

        $requisite = new IndividualRequisite($data);

        $this->companyBranch->individual_requisites()->save($requisite);

        return response()->json($requisite->refresh());
    }


    public function show(Request $request, $id)
    {
        return  $this->companyBranch->individual_requisites()->findOrFail($id);
    }


    /**
     * Update the specified resource in storage.
     * @param Request $request
     * @param int $id
     * @return Response
     */
    public function update(IndividualRequisiteRequest $request, $id)
    {
        $requisite =  $this->companyBranch->individual_requisites()->findOrFail($id);

        $data = $request->all();

        $requisite->update($data);

        return response()->json($requisite->refresh());
    }

    /**
     * Remove the specified resource from storage.
     * @param int $id
     * @return Response
     */
    public function destroy(Request $request, $id)
    {
        $requisite =   $this->companyBranch->individual_requisites()->findOrFail($id);

        $requisite->delete();

        return response()->json();
    }
}
