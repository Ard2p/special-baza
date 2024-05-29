<?php

namespace Modules\Dispatcher\Http\Controllers\Requisites;

use App\Service\RequestBranch;
use App\User\BankRequisite;
use App\User\EntityRequisite;
use App\User\IndividualRequisite;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Modules\CompanyOffice\Services\CompanyRoles;

class BankRequisitesController extends Controller
{

    private $companyBranch;

    public function __construct(Request $request, RequestBranch $companyBranch)
    {
        $this->companyBranch = $companyBranch->companyBranch;

        $block = $this->companyBranch->getBlockName(CompanyRoles::BRANCH_CLIENTS);


        $this->middleware("accessCheck:{$block},".CompanyRoles::ACTION_DELETE)->only(['destroy']);

    }

    /**
     * Remove the specified resource from storage.
     * @param int $id
     * @return Response
     */
    public function destroy($id)
    {
        $bankRequisite = BankRequisite::query()
            ->whereHasMorph('owner', [EntityRequisite::class], function ($q) {
                $q->forBranch();
            })->findOrFail($id);

        $bankRequisite->delete();
    }
}
