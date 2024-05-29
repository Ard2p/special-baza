<?php

namespace Modules\Dispatcher\Http\Controllers;

use App\City;
use App\Helpers\RequestHelper;
use App\Machines\Brand;
use App\Machines\Type;
use App\Service\RequestBranch;
use App\Support\Region;
use App\User\IndividualRequisite;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Modules\AdminOffice\Entities\Filter;
use Modules\CompanyOffice\Services\CompanyRoles;
use Modules\Dispatcher\Entities\Customer;
use Modules\Dispatcher\Entities\Directories\Contractor;
use Modules\Dispatcher\Entities\Directories\Vehicle;
use Modules\Dispatcher\Http\Requests\CreateContractorRequest;
use Modules\Dispatcher\Services\CustomerService;
use Modules\Dispatcher\Transformers\ContractorEdit;
use Modules\Dispatcher\Transformers\ContractorInfo;
use Modules\Dispatcher\Transformers\ContractorsList;
use Modules\RestApi\Entities\KnowledgeBase\Category;

class ContractorsController extends Controller
{

    private $companyBranch;

    public function __construct(Request $request, RequestBranch $companyBranch)
    {
        $this->companyBranch = $companyBranch->companyBranch;
        if ($request->filled('phone')) {
            $request->merge([
                'phone' => trimPhone($request->phone)
            ]);
        }

        $block = $this->companyBranch->getBlockName(CompanyRoles::BRANCH_CONTRACTORS);
        $this->middleware("accessCheck:{$block}," . CompanyRoles::ACTION_SHOW)->only('index');
        $this->middleware("accessCheck:{$block}," . CompanyRoles::ACTION_CREATE)->only(['store', 'update']);
        $this->middleware("accessCheck:{$block}," . CompanyRoles::ACTION_DELETE)->only(['destroy']);

        if ($request->filled('contacts')) {
            $contacts = $request->input('contacts');
            foreach ($contacts as &$contact) {
                $contact['phone'] = trimPhone($contact['phone'] ?? '');
            }
            $request->merge([
                'contacts' => $contacts
            ]);
        }
    }
    /**
     * Display a listing of the resource.
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function index(Request $request)
    {
        $contractors = Contractor::query()->forDomain()->forBranch();

        $filter = new Filter($contractors);

        $filter->getLike([
            'company_name' => 'company_name',
            'address' => 'address',
            'contact_person' => 'contact_person',
            'phone' => 'phone',
        ]);

        $contractors->orderBy('created_at', 'desc');


        return ContractorsList::collection($contractors->paginate($request->per_page ?: 10));
    }

    /**
     * Store a newly created resource in storage.
     * @param Request $request
     * @return Response
     */
    public function store(CreateContractorRequest $request)
    {

        DB::beginTransaction();

        /** @var Contractor $contractor */
        $contractor = Contractor::create([
            'company_name' => $request->input('company_name'),
            'address' => $request->input('address'),
            'region_id' => $request->input('region_id'),
            'city_id' => $request->input('city_id'),
            'contact_person' => $request->input('contact_person'),
            'phone' => $request->input('phone'),
            'creator_id' => Auth::id(),
            'domain_id' => RequestHelper::requestDomain()->id,
            'company_branch_id' => $this->companyBranch->id,
        ]);

        $contractor->addContacts($request->input('contacts'));

        if ($request->filled('has_requisite')) {
            $requisite = $request->input('requisite');
            $requisite['creator_id'] = Auth::id();
            $requisite['company_branch_id'] = $this->companyBranch->id;

            if ($request->input('has_requisite') === 'legal') {

                $contractor->addLegalRequisites($requisite);
            }

            if (in_array($request->input('has_requisite'), [
                IndividualRequisite::TYPE_PERSON,
                IndividualRequisite::TYPE_ENTREPRENEUR,
            ])) {

                $contractor->addIndividualRequisites($requisite);
            }
        }


        if ($request->filled('has_requisite')) {
            $requisite = $request->input('requisite');
            $requisite['creator_id'] = Auth::id();
            $requisite['company_branch_id'] = $this->companyBranch->id;

            if ($request->input('has_requisite') === 'legal') {

                $contractor->addLegalRequisites($requisite);
            }

            if ($request->input('has_requisite') === 'individual') {

                $contractor->addIndividualRequisites($requisite);
            }
        }

        DB::commit();

        return response()->json($contractor);
    }

    /**
     * Show the specified resource.
     * @param int $id
     * @return Response
     */
    public function show($id)
    {
        $contractor = Contractor::forBranch()->forDomain()->findOrFail($id);

        return ContractorEdit::make($contractor);
    }

    /**
     * Update the specified resource in storage.
     * @param Request $request
     * @param int $id
     * @return Response
     */
    public function update(CreateContractorRequest $request, $id)
    {
        $request->validated();
        $contractor = Contractor::forBranch()->findOrFail($id);

        DB::beginTransaction();

        $contractor->update([
            'company_name' => $request->input('company_name'),
            'address' => $request->input('address'),
            'region_id' => $request->input('region_id'),
            'city_id' => $request->input('city_id'),
            'contact_person' => $request->input('contact_person'),
            'phone' => $request->input('phone'),
        ]);

        $contractor->addContacts($request->input('contacts'));

        $requisite = $request->input('requisite');

        if ($request->input('has_requisite') === 'legal') {

            $requisite['creator_id'] = Auth::id();
            $requisite['company_branch_id'] = $this->companyBranch->id;

            $contractor->addLegalRequisites($requisite);
        }


        if (in_array($request->input('has_requisite'), [
            IndividualRequisite::TYPE_PERSON,
            IndividualRequisite::TYPE_ENTREPRENEUR,
        ])) {
            $requisite['creator_id'] = Auth::id();
            $requisite['company_branch_id'] = $this->companyBranch->id;
            $contractor->addIndividualRequisites($requisite);
        }

        $contractor->refresh();
        DB::commit();

        return response()->json($contractor);
    }

    /**
     * Remove the specified resource from storage.
     * @param int $id
     * @return Response
     */
    public function destroy($id)
    {
        $contractor = Contractor::query()->withTrashed()->forBranch()->findOrFail($id);

        if (!$contractor->vehicles()->withTrashed()->exists()) {
            $contractor->forceDelete();
        } else {
            $contractor->delete();
        }

        return response()->json();
    }

    function createHelper()
    {
        $regions = Region::with('cities')->forDomain()->get();
        $categories = Type::all();
        if (\app()->getLocale() !== 'ru') {
            $categories->each->localization();
            $categories = $categories->sortBy('name')->values()->all();
        }

        return response()->json([
            'regions' => $regions,
            'brands' => Brand::all(),
            'categories' => $categories
        ]);
    }

    function getInfo($id)
    {
        $contractor = Contractor::forBranch()->findOrFail($id);

        return ContractorInfo::make($contractor);
    }

    function setSettings(
        Request $request,
                $customer_id
    ) {
        /** @var Contractor $contractor */
        $contractor = Contractor::forBranch()->forDomain()->findOrFail($customer_id);

        $service = new CustomerService($contractor);

        switch ($request->input('type')) {
            case 'contract':
                $request->validate([
                    'prefix' => 'nullable|string|max:10',
                    'postfix' => 'nullable|string|max:10',
                    'number' => 'nullable|max:255',
                    'internal_number' => 'nullable|numeric',
                    'created_at' => 'required|date',
                    'order_type' => 'required|in:rent,service',
                ]);
                $service->changeContractSettings($request->input('number'), $request->input('created_at'),
                    $request->input('internal_number'), $request->input('order_type'));
                break;
            case 'application' :
                $request->validate([
                    'last_application_id' => 'required|numeric|max:9999999|min:'.$contractor->lastApplicationId()
                ]);

                $service->updateLastApplicationId($request->input('last_application_id'));
                break;

        }

        return response()->json();
    }
}
