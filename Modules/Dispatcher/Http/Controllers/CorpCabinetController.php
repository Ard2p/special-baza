<?php

namespace Modules\Dispatcher\Http\Controllers;

use App\Service\RequestBranch;
use App\User;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Modules\CompanyOffice\Services\CompanyRoles;
use Modules\CompanyOffice\Services\InviteEmployeeService;
use Modules\Dispatcher\Entities\Customer;

class CorpCabinetController extends Controller
{

    private $companyBranch;

    public function __construct(Request $request, RequestBranch $companyBranch)
    {
        $this->companyBranch = $companyBranch->companyBranch;

        $block = $this->companyBranch->getBlockName(CompanyRoles::BRANCH_CLIENTS);
        $this->middleware("accessCheck:{$block}," . CompanyRoles::ACTION_SHOW)->only('getUsers', 'inviteUser', 'detachUser');

    }

    function getUsers(Request $request, $customerId)
    {
        /** @var Customer $customer */
        $customer = Customer::forBranch()->findOrFail($customerId);

        return $customer->corpUsers;
    }

    function inviteUser(Request $request, $customerId)
    {
        $request->validate([
            'email' => 'required|email|max:255'
        ]);

        /** @var Customer $customer */
        $customer = Customer::forBranch()->findOrFail($customerId);

            (new InviteEmployeeService())
                ->setCompanyBranch($this->companyBranch)
                ->generateInvite($request->input('email'), "customer_{$customer->id}");


        return response()->json();
    }

    function detachUser(Request $request, $customerId, $userId)
    {
        /** @var Customer $customer */
        $customer = Customer::forBranch()->findOrFail($customerId);

        $customer->corpUsers()->detach($userId);

        return response()->json();
    }
}
