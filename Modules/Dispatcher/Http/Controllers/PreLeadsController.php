<?php

namespace Modules\Dispatcher\Http\Controllers;

use App\Directories\LeadRejectReason;
use App\Helpers\RequestHelper;
use App\Service\RequestBranch;
use App\User\IndividualRequisite;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Modules\AdminOffice\Entities\Filter;
use Modules\CompanyOffice\Services\CompanyRoles;
use Modules\Dispatcher\Entities\Customer;
use Modules\Dispatcher\Entities\PreLead;
use Modules\Dispatcher\Http\Requests\PreLeadRequest;
use Modules\Dispatcher\Http\Requests\PreLeadTransformRequest;
use Modules\Dispatcher\Services\PreLeadService;
use Modules\Dispatcher\Transformers\LeadList;
use Modules\Dispatcher\Transformers\PreLeadCollection;

class PreLeadsController extends Controller
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
        $block = $this->companyBranch->getBlockName(CompanyRoles::BRANCH_PROPOSALS);
        $this->middleware("accessCheck:{$block}," . CompanyRoles::ACTION_SHOW)->only('index');
        $this->middleware("accessCheck:{$block}," . CompanyRoles::ACTION_CREATE)->only(['store', 'update', 'transform',
            'show',]);
        $this->middleware("accessCheck:{$block}," . CompanyRoles::ACTION_DELETE)->only(['destroy']);

    }

    /**
     * Display a listing of the resource.
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     */
    public function index(Request $request)
    {
        $preLeads = PreLead::query()->with(['audits','rejectType', 'manager', 'customer', 'positions.category', 'positions.attributes', 'contacts'])->forBranch();

        if($request->has('ids')) {
            $preLeads->whereIn('id', explode(',', $request->input('ids')));
            return PreLeadCollection::collection($preLeads->get());
        }

        $filter = new Filter($preLeads);
        $filter->getEqual([
            'status' => 'status',
            'customer_id' => 'customer_id',
            'creator_id' => 'creator_id',
        ])->getLike([
            'internal_number' => 'internal_number',
        ]);
        if($request->filled('archive')) {
            $preLeads->whereIn('status', [
                PreLead::STATUS_REJECT,
                PreLead::STATUS_ACCEPT,
            ]);
        }
        if (toBool($request->category_id)) {
            $preLeads->whereRelation('positions', 'category_id', $request->category_id);
        }
        if ($request->first_date_rent) {
            $preLeads->whereRelation('positions', 'date_from', 'like',  "%{$request->first_date_rent}%");
        }
        return PreLeadCollection::collection($preLeads->orderBy('created_at', 'desc')->paginate($request->per_page ?: 10));
    }


    /**
     * Store a newly created resource in storage.
     * @param Request $request
     * @return Response
     */
    public function store(PreLeadRequest $request)
    {
        $service = new PreLeadService($this->companyBranch);

        DB::beginTransaction();
        if ($request->filled('customer_id')) {
            $customer = Customer::forCompany()->findOrFail($request->input('customer_id'));
        } else {
            $customer = Customer::create([
                'email' => $request->input('email'),
                'company_name' => $request->input('customer.company_name'),
                'contact_person' => $request->input('contact_person'),
                'region_id' => $request->input('customer.region_id'),
                'city_id' => $request->input('customer.city_id'),
                'phone' => $request->input('phone'),
                'creator_id' => Auth::id(),
                'company_branch_id' => $this->companyBranch->id,
                'domain_id' => RequestHelper::requestDomain()->id,
            ]);
            $requisite = $request->input('customer.requisite');
            $requisite['creator_id'] = Auth::id();
            $requisite['company_branch_id'] = $this->companyBranch->id;

            // logger($request->input('has_requisite'));
            // DB::rollBack();
            // return response()->json([], 400);
            // die();
            if ($request->input('has_requisite') === 'legal') {

                $customer->addLegalRequisites($requisite);
            }

            if (in_array($request->input('has_requisite'), [
                IndividualRequisite::TYPE_PERSON,
                IndividualRequisite::TYPE_ENTREPRENEUR,
            ])) {

                $customer->addIndividualRequisites($requisite);
            }
            $request->merge(['customer_id' => $customer->id]);
        }

        $service->setData($request->all())->create();

        DB::commit();


        return response()->json($service->preLead);
    }

    /**
     * Show the specified resource.
     * @param int $id
     * @return Response
     */
    public function show( $id)
    {

        $preLead = PreLead::query()->forBranch()->findOrFail($id);

        return PreLeadCollection::make($preLead);
    }

    /**
     * Update the specified resource in storage.
     * @param Request $request
     * @param int $id
     * @return Response
     */
    public function update(PreLeadRequest $request, $id)
    {
        $service = new PreLeadService($this->companyBranch);

      //  Customer::query()->forCompany()->findOrFail($request->input('customer_id'));

        $preLead = PreLead::query()->forBranch()->findOrFail($id);
        DB::beginTransaction();
        $service->setData($request->all())->update($preLead);
        DB::commit();
        return response()->json($service->preLead);
    }

    function transform(PreLeadTransformRequest $request, $id)
    {

        /** @var PreLead $preLead */
        $preLead = PreLead::query()->forBranch()->whereStatus(PreLead::STATUS_OPEN)->findOrFail($id);
        $service = new PreLeadService($this->companyBranch);

        DB::beginTransaction();

        if ($request->filled('customer_id') && $request->input('customer_type') === 'existing') {
            Customer::forCompany()->findOrFail($request->input('customer_id'));
        } else {
            $customer = Customer::create([
                'email' => $request->input('email'),
                'company_name' => $request->input('company_name'),
                'contact_person' => $request->input('contact_person'),
                'phone' => $request->input('phone'),
                'creator_id' => Auth::id(),
                'company_branch_id' =>$this->companyBranch->id,
                'domain_id' => RequestHelper::requestDomain()->id,
            ]);
            $request->merge([
                'customer_id' => $customer->id
            ]);
        }

        $service->setData($request->all())->update($preLead);
        DB::commit();

        DB::beginTransaction();

        $lead = $preLead->transformToLead();

        DB::commit();
        return response()->json($lead);
    }

    function reject(Request $request, $id)
    {
        $request->validate([
            'reject' => 'nullable|string|max:1000',
            'reject_type' => 'required|string|in:' . LeadRejectReason::implodeInString(),

        ]);
        /** @var PreLead $preLead */
        $preLead = PreLead::query()->forBranch()->whereStatus(PreLead::STATUS_OPEN)->findOrFail($id);

        $preLead->reject($request->input('reject_type'), $request->input('reject'));

        return response()->json();
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
