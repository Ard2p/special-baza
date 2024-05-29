<?php

namespace Modules\CompanyOffice\Http\Controllers;

use App\City;
use App\Machines\Type;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Modules\CompanyOffice\Entities\Company;
use Modules\RestApi\Entities\Auth\AuthHash;

class CompaniesController extends Controller
{
    /**
     * Display a listing of the resource.
     * @return Response
     */
    public function index()
    {
        $companies = Company::query()->whereHas('branches', function ($q) {
            $q->whereHas('employees', function ($q) {
                $q->where('users.id', Auth::id());
            });
        });
        return $companies->get();
    }

    function show($alias)
    {
        return Company::query()->where('alias', $alias)->forDomain()->firstOrFail();
    }

    function updateCompanySettings(Request $request, $id)
    {
        $request->validate([
                'name' => 'required|string|max:255',
                'settings.catalog_seo_text' => 'nullable|string|max:21844',
                'settings.about_page_content' =>  'nullable|string|max:21844',
                'settings.contact_address' => 'required|string|max:255',
                'settings.contact_phone' => 'required|string|max:255',
                'settings.contact_email' => 'required|email|max:255',

            ] + Company::getStyleRules());

        $company = Company::query()->userHasAccess()->findOrFail($id);

        DB::beginTransaction();

        $company->update([
            'name' => $request->input('name')
        ]);

        $company->updateStyle($request->input('style'));

        $company->updateSettings($request->input('settings'));

        DB::commit();

        return response()->json();
    }

    /**
     * Получение настроек для компании
     * @param $id
     */
    function getSettings($id)
    {
        $company = Company::query()->findOrFail($id);

        $company->getSettings();

        $company->load('settings');

        return $company;
    }

    /**
     * Достпуные для заказа категории компании
     * @param $id
     */
    function getAvailableCategories($id)
    {
        /** @var Company $company */
        $company = Company::query()->findOrFail($id);

        $categories = Type::query()->whereHas('machines', function ($q) use ($company) {
            $q->forCompany($company->id);
        })->get();

        return Type::setLocaleNames($categories);
    }

    function getAvailableCities(Request $request, $id)
    {
        $company = Company::query()->findOrFail($id);

        $cities = City::query()->with('region')->whereHas('machines', function ($q) use ($company, $request) {
            $q->forCompany($company->id);
            if ($request->filled('category_id')) {
                $q->whereType($request->input('category_id'));
            }
        });

        return $cities->get();
    }

    function getAccessLink($company_id)
    {
        $company = Company::query()->findOrFail($company_id);

        if (Auth::user()->hasAccessToCompany($company->id)) {

            $alias = $company->alias;
            $domain = $company->domain;
            $branch = $company->branches->first();

            $hash = AuthHash::createHash(Auth::id());

            return response()->json([
                'url' => "https://{$alias}.{$domain->pure_url}/branch/{$branch->id}?hash={$hash}"
                //'url' => "https://{$alias}.dev.tb/branch/{$branch->id}?hash={$hash}"
            ]);
        }

        return response()->json([], 403);
    }
}
