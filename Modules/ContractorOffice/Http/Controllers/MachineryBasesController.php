<?php

namespace Modules\ContractorOffice\Http\Controllers;

use App\Machines\Type;
use App\Service\RequestBranch;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Illuminate\Validation\Rule;
use Modules\CompanyOffice\Services\CompanyRoles;
use Modules\ContractorOffice\Entities\Vehicle\MachineryBase;
use Modules\Integrations\Rules\Coordinates;

class MachineryBasesController extends Controller
{

    private $companyBranch;

    public function __construct(Request $request, RequestBranch $companyBranch)
    {
        $this->companyBranch = $companyBranch->companyBranch;

        $block = $this->companyBranch->getBlockName(CompanyRoles::BRANCH_CALENDAR);
        $this->middleware("accessCheck:{$block},".CompanyRoles::ACTION_SHOW)->only('index', 'show');
        $this->middleware("accessCheck:{$block},".CompanyRoles::ACTION_CREATE)->only(['store', 'update']);
        $this->middleware("accessCheck:{$block},".CompanyRoles::ACTION_DELETE)->only(['destroy']);


    }

    /**
     * Display a listing of the resource.
     * @return Response
     */
    public function index(Request $request)
    {
        $bases = MachineryBase::query()->with('companyWorker.contacts')->withCount('machineries')->forBranch();

        if ($request->filled('noPagination')) {
            return $bases->get();
        }
        return $bases->paginate($request->per_page
            ?: 15);
    }


    /**
     * Store a newly created resource in storage.
     * @param  Request  $request
     * @return Response
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'address' => 'required|string|max:255',
            'kpp' => 'nullable|string|max:255',
            'company_worker_id' => 'nullable|exists:company_workers,id',
            'city_id' => [
                'required',
                Rule::exists('cities', 'id')->where('region_id', $request->input('region_id'))
            ],
            'region_id' => 'required|exists:regions,id',
            'insurance_premium' => 'required|numeric|min:0',
            'cancel_after' => 'nullable|numeric|min:1',
            'payment_percent' => 'nullable|numeric|min:1|max:100',
            'coordinates' => [
                'required',
                new Coordinates()
            ],
        ]);
        $base = MachineryBase::create([
            'name' => $request->input('name'),
            'city_id' => $request->input('city_id'),
            'region_id' => $request->input('region_id'),
            'company_worker_id' => $request->input('company_worker_id'),
            'address' => $request->input('address'),
            'kpp' => $request->input('kpp'),
            'coordinates' => $request->input('coordinates'),
            'insurance_premium' => $request->input('insurance_premium'),
            'cancel_after' => $request->input('cancel_after'),
            'payment_percent' => $request->input('payment_percent'),
            'company_branch_id' => $this->companyBranch->id,

        ]);

        return $base;
    }

    /**
     * Show the specified resource.
     * @param  int  $id
     * @return Response
     */
    public function show($id)
    {
        return MachineryBase::query()->forBranch()->findOrFail($id);
    }


    /**
     * Update the specified resource in storage.
     * @param  Request  $request
     * @param  int  $id
     * @return Response
     */
    public function update(
        Request $request,
        $id
    ) {
        $request->validate([
            'name' => 'required|string|max:255',
            'address' => 'required|string|max:255',
            'kpp' => 'nullable|string|max:255',
            'company_worker_id' => 'nullable|exists:company_workers,id',
            'insurance_premium' => 'required|numeric|min:0',
            'city_id' => [
                'required',
                Rule::exists('cities', 'id')->where('region_id', $request->input('region_id'))
            ],
            'region_id' => 'required|exists:regions,id',
            'cancel_after' => 'nullable|numeric|min:1',
            'payment_percent' => 'nullable|numeric|min:1|max:100',
            'coordinates' => [
                'required',
                new Coordinates()
            ],
        ]);
        $machineryBase = MachineryBase::query()->forBranch()->findOrFail($id);

        $machineryBase->update($request->only([
            'name',
            'address',
            'company_worker_id',
            'city_id',
            'region_id',
            'insurance_premium',
            'kpp',
            'coordinates',
            'cancel_after',
            'payment_percent',

        ]));

        return $machineryBase;
    }

    /**
     * Remove the specified resource from storage.
     * @param  int  $id
     * @return Response
     */
    public function destroy($id)
    {
        MachineryBase::query()->forBranch()->findOrFail($id)->delete();

        return response()->json();
    }

    public function types($id)
    {
        $types =
            Type::query()->whereHas('machines.base', function ($q) use (
                $id
            ) {
                $q->where('id', $id);
            })->get()->map->only(['name', 'id']);

        return response()->json($types);
    }
}
