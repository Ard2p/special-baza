<?php

namespace Modules\AdminOffice\Http\Controllers\Companies;

use App\User;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Modules\AdminOffice\Entities\Filter;
use Modules\AdminOffice\Transformers\AdminCompanyBranchesList;
use Modules\CompanyOffice\Entities\Company\CompanyBranch;
use Modules\CompanyOffice\Services\CompanyRoles;
use Modules\CompanyOffice\Services\InviteEmployeeService;


class CompanyBranchesController extends Controller
{
    /**
     * Display a listing of the resource.
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     */
    public function index(Request $request)
    {
        $companies = CompanyBranch::query()->with('employees', 'region', 'city');

        $filter = new Filter($companies);

        $filter->getLike([
            'name' => 'name'
        ]);

        $companies->orderBy('id', 'desc');

        return AdminCompanyBranchesList::collection($companies->paginate($request->per_page ?: 20));
    }


    /**
     * Store a newly created resource in storage.
     * @param Request $request
     * @return Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Show the specified resource.
     * @param int $id
     * @return \Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Eloquent\Builder[]|\Illuminate\Database\Eloquent\Collection|\Illuminate\Database\Eloquent\Model
     */
    public function show($id)
    {
        $companies = CompanyBranch::query()->with('employees', 'region', 'city', 'entity_requisites', 'individual_requisites');
        return $companies->findOrFail($id);
    }

    /**
     * Show the form for editing the specified resource.
     * @param int $id
     * @return Response
     */
    public function edit($id)
    {
        return view('adminoffice::edit');
    }

    /**
     * Update the specified resource in storage.
     * @param Request $request
     * @param int $id
     * @return Response
     */
    public function update(Request $request, $id)
    {
        //
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

    function totalDelete($id)
    {
        /** @var CompanyBranch $companyBranch */
        $companyBranch = CompanyBranch::query()->findOrFail($id);

        \DB::beginTransaction();

        $companyBranch->delete();

        $companyBranch->company->delete();

        \DB::commit();

        return response()->json();

    }

    function inviteUser(Request $request, $branch_id)
    {
        $request->validate([
            'email' => 'required|email',
            'role' => 'required|in:' . implode(',', array_keys(CompanyRoles::getBranchRoles()))
        ]);
        $email = $request->input('user_id')
            ? User::query()->findOrFail($request->input('user_id'))->email
            : $request->input('email');
        /** @var CompanyBranch $branch */
        $branch = CompanyBranch::query()->findOrFail($branch_id);

        (new InviteEmployeeService())
            ->setCompanyBranch($branch)
            ->generateInvite($email, $request->input('role'));


        return response()->json();
    }

    function deleteUser()
    {

    }

    function getBranchRoles()
    {
        $roles = [];

        foreach (array_keys(CompanyRoles::getBranchRoles()) as $role) {
            $roles[] = [
                'name' => trans('transbaza_roles.' . $role),
                'value' => $role,
            ];
        }

        return response()->json($roles);
    }
}
