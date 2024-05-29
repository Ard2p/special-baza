<?php

namespace Modules\CompanyOffice\Http\Controllers\Requisites;

use App\Helpers\RequestHelper;
use App\Service\RequestBranch;
use App\User\EntityRequisite;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Modules\CompanyOffice\Services\CompanyRoles;
use Modules\CorpCustomer\Entities\InternationalLegalDetails;
use Modules\Dispatcher\Http\Requests\RequisitesRequest;

class LegalController extends Controller
{
    private $companyBranch;

    public function __construct(Request $request, RequestBranch $companyBranch)
    {
        $this->companyBranch = $companyBranch->companyBranch;

        $block = $this->companyBranch->getBlockName(CompanyRoles::BRANCH_PAYMENTS);

        $this->middleware("accessCheck:{$block}," . CompanyRoles::ACTION_SHOW)->only('index');
        $this->middleware("accessCheck:{$block}," . CompanyRoles::ACTION_CREATE)->only(['store', 'update']);
        $this->middleware("accessCheck:{$block}," . CompanyRoles::ACTION_DELETE)->only(['destroy', 'closeLead']);

    }

    /**
     * Display a listing of the resource.
     * @param Request $request
     * @return Response
     */
    public function index(Request $request)
    {

        return RequestHelper::requestDomain()->alias === 'ru'
            ? $this->companyBranch->entity_requisites
            : $this->companyBranch->international_legal_requisites;
    }


    /**
     * Store a newly created resource in storage.
     * @param Request $request
     * @return Response
     */
    public function store(RequisitesRequest $request)
    {
      /*  if($this->companyBranch->hasRequisite) {
            return  response()->json([
                'message' => 'Ошибка. Реквизиты уже заполнены.'
            ], 400);
        }*/

        $data = $request->all();

        $user = $request->user('api');

        $data['creator_id'] = $user->id;
        $data['company_branch_id'] = $this->companyBranch->id;

        $requisite = RequestHelper::requestDomain()->alias === 'ru'
            ? new EntityRequisite($data)
            : new InternationalLegalDetails($data);

        RequestHelper::requestDomain()->alias === 'ru'
            ? $this->companyBranch->entity_requisites()->save($requisite)
            : $this->companyBranch->international_legal_requisites()->save($requisite);

        if($requisite instanceof EntityRequisite) {
            $requisite->addBankRequisites($request->bank_requisites ?: []);
        }

        return response()->json($requisite->refresh());
    }

    /**
     * Show the specified resource.
     * @param int $id
     * @return Response
     */
    public function show($id)
    {

        return RequestHelper::requestDomain()->alias === 'ru'
            ? $this->companyBranch->entity_requisites()->findOrFail($id)
            : $this->companyBranch->international_legal_requisites()->findOrFail($id);
    }

    /**
     * Update the specified resource in storage.
     * @param Request $request
     * @param int $id
     * @return Response
     */
    public function update(RequisitesRequest $request, $id)
    {

        $requisite = RequestHelper::requestDomain()->alias === 'ru'
            ? $this->companyBranch->entity_requisites()->findOrFail($id)
            : $this->companyBranch->international_legal_requisites()->findOrFail($id);

        $data = $request->all();

        $requisite->update($data);


        if($requisite instanceof EntityRequisite) {
            $requisite->addBankRequisites($request->bank_requisites ?: []);
        }

        return response()->json($requisite->refresh());
    }

    /**
     * Remove the specified resource from storage.
     * @param int $id
     * @return Response
     */
    public function destroy($id)
    {

        $requisite = RequestHelper::requestDomain()->alias === 'ru'
            ? $this->companyBranch->entity_requisites()->findOrFail($id)
            : $this->companyBranch->international_legal_requisites()->findOrFail($id);

        $requisite->delete();

        return response()->json();
    }
}
