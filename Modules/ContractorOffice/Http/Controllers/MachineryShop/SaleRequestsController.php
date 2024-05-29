<?php

namespace Modules\ContractorOffice\Http\Controllers\MachineryShop;

use App\Service\RequestBranch;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Modules\CompanyOffice\Services\CompanyRoles;
use Modules\ContractorOffice\Entities\Vehicle\Shop\MachinerySaleRequest;
use Modules\ContractorOffice\Entities\Vehicle\Shop\MachinerySaleRequestPosition;
use Modules\ContractorOffice\Http\Requests\Vehicle\Shop\CreateMachinerySaleRequest;
use Modules\ContractorOffice\Transformers\Vehicle\Shop\MachinerySaleRequestResource;
use Modules\Dispatcher\Entities\Customer;

class SaleRequestsController extends Controller
{
    private $companyBranch;

    public function __construct(Request $request, RequestBranch $companyBranch)
    {
        $this->companyBranch = $companyBranch->companyBranch;

        $block = $this->companyBranch->getBlockName(CompanyRoles::BRANCH_PROPOSALS);

        $this->middleware("accessCheck:{$block}," . CompanyRoles::ACTION_SHOW)->only('index', 'show', 'getContract');



        $this->middleware("accessCheck:{$block}," . CompanyRoles::ACTION_CREATE)->only(['store', 'update', 'sale', 'deleteContract', 'addContract']);
        $this->middleware("accessCheck:{$block}," . CompanyRoles::ACTION_DELETE)->only(['destroy']);
    }

    /**
     * Display a listing of the resource.
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function index(Request $request)
    {
        /** @var Builder $saleRequests */
        $saleRequests = MachinerySaleRequest::query()->with(
            'positions.category',
            'customer',
            'sales',
            'contract',
        )->forBranch();

        return $saleRequests->orderBy('created_at', 'desc')->paginate($request->per_page ?: 15);
    }


    /**
     * Store a newly created resource in storage.
     * @param Request $request
     * @return Response
     */
    public function store(CreateMachinerySaleRequest $request)
    {
        DB::beginTransaction();
        if ($request->filled('customer_id')) {
            Customer::query()->forBranch()->findOrFail($request->input('customer_id'));
        }
        /** @var MachinerySaleRequest $saleRequest */
        $saleRequest = MachinerySaleRequest::create([
            'date' => $request->input('date'),
            'customer_id' => $request->input('customer_id'),
            'phone' => $request->input('phone'),
            'pay_type' => $request->input('pay_type'),
            'currency' => $request->input('currency'),
            'email' => $request->input('email'),
            'contact_person' => $request->input('contact_person'),
            'company_branch_id' => $this->companyBranch->id,
        ]);

        $saleRequest->createDefaultContract();

        foreach ($request->input('positions') as $position) {
            $saleRequest->positions()->save(new MachinerySaleRequestPosition([

                'category_id' => $position['category_id'],
                'model_id' => $position['model_id'] ?? null,
                'brand_id' => $position['brand_id'] ?? null,
                'year' => $position['year'] ?? null,
                'engine_hours' => $position['engine_hours'] ?? null,
                'comment' => $position['comment'] ?? null,
                'amount' => $position['amount'],
            ]));
        }

        DB::commit();
    }

    /**
     * Show the specified resource.
     * @param int $id
     * @return Response
     */
    public function show($id)
    {
        return MachinerySaleRequestResource::make(MachinerySaleRequest::query()
            ->with(
                'positions.category',
                'customer',
                'sales',
                'contract',
            )
            ->forBranch()->findOrFail($id));
    }


    /**
     * Update the specified resource in storage.
     * @param Request $request
     * @param int $id
     * @return Response
     */
    public function update(CreateMachinerySaleRequest $request, $id)
    {
        /** @var MachinerySaleRequest $saleRequest */
        $saleRequest = MachinerySaleRequest::query()->forBranch()->findOrFail($id);

        DB::beginTransaction();

        if ($request->filled('customer_id')) {
            Customer::query()->forBranch()->findOrFail($request->input('customer_id'));
        }
        $saleRequest->update([
            'date' => $request->input('date'),
            'customer_id' => $request->input('customer_id'),
            'phone' => $request->input('phone'),
            'pay_type' => $request->input('pay_type'),
            'email' => $request->input('email'),
            'currency' => $request->input('currency'),
            'contact_person' => $request->input('contact_person'),
            'company_branch_id' => $this->companyBranch->id,
        ]);
        $saleRequest->positions()->delete();

        foreach ($request->input('positions') as $position) {
            $saleRequest->positions()->save(new MachinerySaleRequestPosition([

                'category_id' => $position['category_id'],
                'model_id' => $position['model_id'] ?? null,
                'brand_id' => $position['brand_id'] ?? null,
                'year' => $position['year'] ?? null,
                'engine_hours' => $position['engine_hours'] ?? null,
                'comment' => $position['comment'] ?? null,
                'amount' => $position['amount'],
            ]));
        }

        DB::commit();
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


    function sale(Request $request, $id)
    {
        $request->validate([
            'account_date' => 'required|date|max:255',
            'account_number' => 'required|max:255',
            'items' => 'required|array',
            'items.*.id' => [
                'required',
                'distinct',
                Rule::exists('machineries', 'id')->where('company_branch_id', $this->companyBranch->id)
            ],
            'items.*.cost' => 'required|numeric|min:1',
        ]);
        $saleRequest = MachinerySaleRequest::query()->forBranch()->findOrFail($id);
        DB::beginTransaction();

        $saleRequest->sale($request->all());

        DB::commit();
        return response()->json();
    }

    function getContract(Request $request, $id)
    {
        /** @var MachinerySaleRequest $saleRequest */
        $saleRequest = MachinerySaleRequest::query()->forBranch()->findOrFail($id);

        return $saleRequest->contract;
    }

    function deleteContract(Request $request, $id)
    {
        $saleRequest = MachinerySaleRequest::query()->forBranch()->findOrFail($id);

        return $saleRequest->contract->remove();
    }

    function addContract(Request $request, $id)
    {
        $saleRequest = MachinerySaleRequest::query()->forBranch()->findOrFail($id);

        $request->validate([
            'name' => 'required|string|max:255',
            'doc' => 'required|string|max:255',
        ]);
        $tmp_dir = config('app.upload_tmp_dir');

        $tmp_file_path = $request->input('doc');

        $exists = Storage::disk()->exists($request->input('doc'));

        if (!$exists || !Str::contains($tmp_file_path, $tmp_dir)) {
            return response()->json(['doc' => ['Файл не найден. Попробуйте еще раз.']], 400);
        }


        return  $saleRequest->addContract($request->input('name'), $tmp_file_path);
    }
}
