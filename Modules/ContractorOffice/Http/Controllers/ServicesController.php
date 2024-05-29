<?php

namespace Modules\ContractorOffice\Http\Controllers;

use App\Service\RequestBranch;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\AdminOffice\Entities\Filter;
use Modules\CompanyOffice\Services\CompanyRoles;
use Modules\ContractorOffice\Entities\Services\CustomService;

class ServicesController extends Controller
{

    private $companyBranch;

    public function __construct(Request $request, RequestBranch $companyBranch)
    {
        $this->companyBranch = $companyBranch->companyBranch;
        $block = $this->companyBranch->getBlockName(CompanyRoles::BRANCH_VEHICLES);
        $this->middleware("accessCheck:{$block}," . CompanyRoles::ACTION_SHOW)->only([
            'index',
        ]);
        $this->middleware("accessCheck:{$block}," . CompanyRoles::ACTION_CREATE)->only(['store', 'update']);

        $this->middleware("accessCheck:{$block}," . CompanyRoles::ACTION_DELETE)->only(['destroy']);

    }


    /**
     * Display a listing of the resource.
     * @return Response
     */
    public function index(Request $request)
    {
        $query = CustomService::query()->with('categories')->forBranch()->orderBy('name');

        if ($request->filled('category_id')) {
            $query->whereHas('categories', function (Builder $q) use
            (
                $request
            ) {
                $q->whereIn('types.id', Arr::wrap($request->input('category_id')));
            });
        }
        $filter = new Filter($query);
        $filter->getLike([
            'name' => 'name'
        ]);
        $data =
            $request->filled('noPagination')
                ? $query->get()
                : $query->paginate($request->per_page
                ?: 20);

        foreach ($data as $item) {
            foreach ($item->categories as $category) {
                $category->localization();
            }
        }

        return $data;
    }


    /**
     * Store a newly created resource in storage.
     * @param Request $request
     * @return Response
     */
    public function store(Request $request)
    {
        $request->validate([
            'name'         => 'required|string|max:255',
            'vendor_code'  => 'nullable|string|max:255',
            'price'        => 'required|numeric|min:0',
            'value_added'        => 'nullable|numeric|min:0',
            'value_added_cashless'        => 'nullable|numeric|min:0',
            'value_added_cashless_vat'        => 'nullable|numeric|min:0',
            'price_cashless'     => 'nullable|numeric|min:0',
            'price_cashless_vat' => 'nullable|numeric|min:0',
            'unit_id'            => 'nullable|exists:units,id',
            'categories'   => 'required|array',
            'categories.*' => 'required|exists:types,id',
        ]);

        DB::beginTransaction();

        $service = CustomService::create([
            'name'               => $request->input('name'),
            'vendor_code'        => $request->input('vendor_code'),
            'is_pledge'          => toBool($request->input('is_pledge')),
            'company_branch_id'  => $this->companyBranch->id,
            'unit_id'            => $request->input('unit_id'),
            'is_for_service'            => $request->input('is_for_service'),
            'price'              => numberToPenny($request->input('price')),
            'price_cashless'     => numberToPenny($request->input('price_cashless') ?: 0),
            'price_cashless_vat' => numberToPenny($request->input('price_cashless_vat') ?: 0),
            'value_added'              => numberToPenny($request->input('value_added') ?: 0),
            'value_added_cashless'              => numberToPenny($request->input('value_added_cashless') ?: 0),
            'value_added_cashless_vat'              => numberToPenny($request->input('value_added_cashless_vat') ?: 0),
        ]);

        $service->categories()->sync($request->input('categories'));

        DB::commit();

        return response()->json();

    }

    /**
     * Show the specified resource.
     * @param int $id
     * @return Response
     */
    public function show($id)
    {
        return CustomService::forBranch()->findOrFail($id);
    }


    /**
     * Update the specified resource in storage.
     * @param Request $request
     * @param int $id
     * @return Response
     */
    public function update(
        Request $request,
                $id)
    {
        $request->validate([
            'name'               => 'required|string|max:255',
            'price'              => 'required|numeric|min:0',
            'value_added'              => 'required|numeric|min:0',
            'value_added_cashless'              => 'required|numeric|min:0',
            'value_added_cashless_vat'              => 'required|numeric|min:0',
            'price_cashless'     => 'nullable|numeric|min:0',
            'price_cashless_vat' => 'nullable|numeric|min:0',
            'vendor_code'        => 'nullable|string|max:255',
            'unit_id'            => 'nullable|exists:units,id',
            'categories'         => 'required|array',
            'categories.*'       => 'required|exists:types,id',
        ]);

        $service = CustomService::forBranch()->findOrFail($id);
        DB::beginTransaction();

        $service->update([
            'name'               => $request->input('name'),
            'vendor_code'        => $request->input('vendor_code'),
            'is_pledge'          => toBool($request->input('is_pledge')),
            'is_for_service'          => toBool($request->input('is_for_service')),
            'unit_id'            => $request->input('unit_id'),
            'price'              => numberToPenny($request->input('price')),
            'price_cashless'     => numberToPenny($request->input('price_cashless')
                ?: 0),
            'price_cashless_vat' => numberToPenny($request->input('price_cashless_vat')
                ?: 0),
            'value_added'              => numberToPenny($request->input('value_added') ?: 0),
            'value_added_cashless'              => numberToPenny($request->input('value_added_cashless') ?: 0),
            'value_added_cashless_vat'              => numberToPenny($request->input('value_added_cashless_vat') ?: 0),
        ]);

        $service->categories()->sync($request->input('categories'));

        DB::commit();

        return response()->json();
    }

    /**
     * Remove the specified resource from storage.
     * @param int $id
     * @return Response
     */
    public function destroy($id)
    {
        $service = CustomService::forBranch()->findOrFail($id);

        if($service->serviceComponent()->exists() || $service->serviceCenters()->exists()) {
            throw ValidationException::withMessages([
                'errors' => ["Невозможно удалить услугу использующуюся в сервисах/сделках."]
            ]);
        }
        $service->delete();

    }
}
