<?php

namespace Modules\PartsWarehouse\Http\Controllers;

use App\Service\RequestBranch;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Modules\CompanyOffice\Entities\Company\CompanyBranch;
use Modules\CompanyOffice\Services\CompanyRoles;
use Modules\PartsWarehouse\Entities\PartsProvider;
use Modules\PartsWarehouse\Http\Requests\PartsProviderRequest;

class ProvidersController extends Controller
{

    /** @var CompanyBranch */
    private $companyBranch;

    public function __construct(Request $request, RequestBranch $companyBranch)
    {
        $this->companyBranch = $companyBranch->companyBranch;
        $block = $this->companyBranch->getBlockName(CompanyRoles::BRANCH_PROPOSALS);
        $this->middleware("accessCheck:{$block}," . CompanyRoles::ACTION_SHOW)->only([
            'index', 'show',
        ]);

        $this->middleware("accessCheck:{$block}," . CompanyRoles::ACTION_CREATE)->only([
            'store',
            'update',
        ]);

        $this->middleware("accessCheck:{$block}," . CompanyRoles::ACTION_DELETE)->only(['destroy']);


    }
    /**
     * Display a listing of the resource.
     * @return Response
     */
    public function index(Request $request)
    {
        $providers = PartsProvider::query()->forBranch();

        return $providers->paginate($request->per_page ?: 15);
    }


    /**
     * Store a newly created resource in storage.
     * @param Request $request
     * @return Response
     */
    public function store(PartsProviderRequest $request)
    {
        DB::beginTransaction();

        /** @var PartsProvider $provider */
        $provider = PartsProvider::create([
            'company_name' => $request->input('company_name'),
            'address' => $request->input('address'),
            'region_id' => $request->input('region_id'),
            'city_id' => $request->input('city_id'),
            'email' => $request->input('email'),
            'contact_person' => $request->input('contact_person'),
            'phone' => $request->input('phone'),
            'creator_id' => \Auth::id(),
            'company_branch_id' => $this->companyBranch->id,
        ]);
        $provider->addContacts($request->input('contacts'));

        if ($request->filled('has_requisite')) {
            $requisite = $request->input('requisite');
            $requisite['creator_id'] = \Auth::id();
            $requisite['company_branch_id'] = $this->companyBranch->id;

            if ($request->input('has_requisite') === 'legal') {

                $provider->addLegalRequisites($requisite);
            }

            if ($request->input('has_requisite') === 'individual') {

                $provider->addIndividualRequisites($requisite);
            }
        }


        DB::commit();

        return response()->json($provider);
    }

    /**
     * Show the specified resource.
     * @param int $id
     * @return Response
     */
    public function show($id)
    {
        $provider = PartsProvider::query()->forBranch()->findOrFail($id);

        return $provider;
    }

    /**
     * Update the specified resource in storage.
     * @param Request $request
     * @param int $id
     * @return Response
     */
    public function update(PartsProviderRequest $request, $id)
    {

        /** @var PartsProvider $provider */
        $provider = PartsProvider::query()->forBranch()->findOrFail($id);

        $provider->update([
            'company_name' => $request->input('company_name'),
            'address' => $request->input('address'),
            'region_id' => $request->input('region_id'),
            'city_id' => $request->input('city_id'),
            'email' => $request->input('email'),
            'contact_person' => $request->input('contact_person'),
            'phone' => $request->input('phone'),
        ]);

        return response()->json($provider);
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
}
