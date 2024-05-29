<?php

namespace Modules\Dispatcher\Http\Controllers\Requisites;

use App\Service\RequestBranch;
use App\User\EntityRequisite;
use App\User\IndividualRequisite;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\MissingValue;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Modules\CompanyOffice\Services\CompanyRoles;
use Modules\Dispatcher\Entities\Customer;
use Modules\Dispatcher\Http\Requests\IndividualRequisiteRequest;

class CustomerIndividualController extends Controller
{

    private $customer, $companyBranch;

    public function __construct(Request $request, RequestBranch $companyBranch)
    {
        $this->companyBranch = $companyBranch->companyBranch;

        $block = $this->companyBranch->getBlockName(CompanyRoles::BRANCH_PROPOSALS);

        $this->middleware("accessCheck:{$block}," . CompanyRoles::ACTION_SHOW)->only([
            'index',
        ]);
        $this->middleware("accessCheck:{$block}," . CompanyRoles::ACTION_CREATE)->only([
            'store', 'update',
        ]);
        $this->middleware("accessCheck:{$block}," . CompanyRoles::ACTION_DELETE)->only([
            'destroy'
        ]);
        $this->customer = Customer::query()
            ->forBranch($this->companyBranch->id)
            ->findOrFail($request->input('customer_id'));
    }

    public function index()
    {
        return $this->customer->individual_requisites ? [$this->customer->individual_requisites] : [];
    }


    /**
     * Store a newly created resource in storage.
     * @param Request $request
     * @return Response
     */
    public function store(IndividualRequisiteRequest $request)
    {
        if($this->customer->hasRequisite()) {
            return  response()->json(['inn' => ['У данного заказчика уже есть реквизиты.']], 400);
        }
        $data = $request->all();

        $data['creator_id'] = Auth::id();
        $data['company_branch_id'] = $this->companyBranch->id;

        $requisite = $this->customer->addIndividualRequisites($data);

        return response()->json($requisite->refresh());
    }

    /**
     * Show the specified resource.
     * @param int $id
     * @return Response
     */
    public function show($id)
    {
        return $this->customer->individual_requisites()->findOrFail($id);
    }

    /**
     * Update the specified resource in storage.
     * @param Request $request
     * @param int $id
     * @return Response
     */
    public function update(IndividualRequisiteRequest $request, $id)
    {
        $data = $request->all();

        $requisite = $this->customer->individual_requisites()->findOrFail($id);

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
        $this->customer->individual_requisites()->findOrFail($id)->delete();

        return response()->json();
    }
}
