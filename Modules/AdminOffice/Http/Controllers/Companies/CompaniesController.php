<?php

namespace Modules\AdminOffice\Http\Controllers\Companies;

use App\City;
use App\User;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Modules\AdminOffice\Transformers\AdminCompanyBranchesList;
use Modules\CompanyOffice\Entities\Company;
use Modules\CompanyOffice\Entities\Company\CompanyBranch;
use Modules\CompanyOffice\Services\CompaniesService;
use Modules\RestApi\Entities\Domain;
use Illuminate\Validation\Rule;

class CompaniesController extends Controller
{
    /**
     * Display a listing of the resource.
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     */
    public function index(Request $request)
    {
        $companies = Company::query();


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
        $request->validate([
            'name' => 'required|string',
            'alias' => 'required|string',
            'phone' => 'required|string',
            'email' => 'required|email',
            'password' => 'required|string|min:6',
            'city_id' => ['required', Rule::exists(City::class, 'id')],
        ]);

        DB::beginTransaction();

        $city = City::findOrFail($request->input('city_id'));
        $user = User::create([
            'email' => $request->input('email'),
            'phone' => trimPhone($request->input('phone')),
            'phone_confirm' => 1,
            'contractor_alias_enable' => 1,
            'password' => Hash::make($request->input('password')),
            'country_id' => $city->region->country_id,
            'native_region_id' => $city->region_id,
            'native_city_id' => $city->id,
            'contact_person' => $request->input('contact_person'),
        ]);

        $domain = Domain::query()->where('alias', 'ru')->first();
        $service = CompaniesService::createCompany($user, $domain->id, $request->input('name'),  generateChpu($request->input('alias')));

        $service->createBranch($request->input('company_name'), $city->region_id, $city->id);

        DB::commit();
    }

    /**
     * Show the specified resource.
     * @param int $id
     * @return \Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Eloquent\Builder[]|\Illuminate\Database\Eloquent\Collection|\Illuminate\Database\Eloquent\Model
     */
    public function show($id)
    {
        $company = Company::query()->with('settings')->findOrFail($id);
        $company->getSettings();

        $company->load('settings');

        return $company;
    }

    /**
     * Update the specified resource in storage.
     * @param Request $request
     * @param int $id
     * @return Response
     */
    public function update(Request $request, $id)
    {
        $request->validate([
                'name' => 'required|string|max:255',
                'alias' => 'required|max:255|unique:companies,alias,' . $id,
                'settings.catalog_seo_text' => 'nullable|string|max:21844',
                'settings.about_page_content' =>  'nullable|string|max:21844',
                'settings.contact_address' => 'required|string|max:255',
                'settings.contact_phone' => 'required|string|max:255',
                'settings.contact_email' => 'required|email|max:255',

            ] + Company::getStyleRules());
        $company = Company::query()->with('settings')->findOrFail($id);

        DB::beginTransaction();

        $company->update([
            'name' => $request->input('name'),
            'alias' => $request->input('alias'),
        ]);

        $company->updateStyle($request->input('style'));

        $company->updateSettings($request->input('settings'), true);

        DB::commit();

        return  response()->json();
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
}
