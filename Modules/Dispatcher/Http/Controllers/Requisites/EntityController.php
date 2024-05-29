<?php

namespace Modules\Dispatcher\Http\Controllers\Requisites;

use App\Service\RequestBranch;
use App\User\EntityRequisite;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Modules\CompanyOffice\Services\CompanyRoles;
use Modules\CorpCustomer\Entities\InternationalLegalDetails;
use Modules\Dispatcher\Entities\Directories\Contractor;
use Modules\Dispatcher\Http\Requests\RequisitesRequest;

class EntityController extends Controller
{

    private $contractor, $companyBranch;

    public function __construct(Request $request, RequestBranch $companyBranch)
    {
        $this->companyBranch = $companyBranch->companyBranch;
        $block = $this->companyBranch->getBlockName(CompanyRoles::BRANCH_CONTRACTORS);

        $this->middleware("accessCheck:{$block}," . CompanyRoles::ACTION_SHOW)->only('index');
        $this->middleware("accessCheck:{$block}," . CompanyRoles::ACTION_CREATE)->only(['store', 'update']);
        $this->middleware("accessCheck:{$block}," . CompanyRoles::ACTION_DELETE)->only(['destroy']);

        $this->contractor = Contractor::query()
            ->forBranch()
            ->findOrFail($request->input('contractor_id'));
    }

    /**
     * Display a listing of the resource.
     * @return Response
     */
    public function index(Request $request)
    {

        return $this->contractor->entity_requisites;
    }

    /**
     * Store a newly created resource in storage.
     * @param Request $request
     * @return Response
     */
    public function store(RequisitesRequest $request)
    {
        $data = $request->all();

        $data['creator_id'] = Auth::id();
        $data['company_branch_id'] = $this->companyBranch->id;

        $requisite = $this->contractor->addLegalRequisites($data);

        return response()->json($requisite->refresh());

    }

    /**
     * Show the specified resource.
     * @param int $id
     * @return Response
     */
    public function show($id)
    {
        return $this->contractor->entity_requisites()->findOrFail($id);
    }

    /**
     * Update the specified resource in storage.
     * @param Request $request
     * @param int $id
     * @return Response
     */
    public function update(RequisitesRequest $request, $id)
    {
        $data = $request->all();

        $requisite = $this->contractor->entity_requisites()->findOrFail($id);

        $requisite->update($data);

        return response()->json($requisite);
    }

    /**
     * Remove the specified resource from storage.
     * @param int $id
     * @return Response
     */
    public function destroy($id)
    {
        $this->contractor->entity_requisites()->findOrFail($id)->delete();

        return response()->json();
    }
}
