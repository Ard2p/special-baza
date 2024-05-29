<?php

namespace Modules\PartsWarehouse\Http\Controllers;

use App\Directories\Unit;
use App\Machines\Brand;
use App\Machines\Type;
use App\Service\RequestBranch;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Modules\AdminOffice\Http\Requests\WarehousePartRequest;
use Modules\CompanyOffice\Entities\Company\CompanyBranch;
use Modules\CompanyOffice\Services\CompanyRoles;
use Modules\ContractorOffice\Entities\System\TariffGrid;
use Modules\ContractorOffice\Entities\System\TariffGridPrice;
use Modules\ContractorOffice\Entities\System\TariffUnitCompare;
use Modules\PartsWarehouse\Entities\PartsProvider;
use Modules\PartsWarehouse\Entities\Stock\Item;
use Modules\PartsWarehouse\Entities\Stock\Stock;
use Modules\PartsWarehouse\Entities\Warehouse\CompanyBranchWarehousePart;
use Modules\PartsWarehouse\Entities\Warehouse\Part;
use Modules\PartsWarehouse\Entities\Warehouse\PartsGroup;

class PartsWarehouseController extends Controller
{
    /** @var CompanyBranch */
    private $companyBranch;

    public function __construct(Request $request, RequestBranch $companyBranch)
    {
        $this->companyBranch = $companyBranch->companyBranch;
        $block = $this->companyBranch->getBlockName(CompanyRoles::BRANCH_PROPOSALS);
        $this->middleware("accessCheck:{$block},".CompanyRoles::ACTION_SHOW)->only([
            'index', 'show',
        ]);

        $this->middleware("accessCheck:{$block},".CompanyRoles::ACTION_CREATE)->only([
            'addToDirectory',
            'addToDirectory',
            'removeFromDirectory',

        ]);

        $this->middleware("accessCheck:{$block},".CompanyRoles::ACTION_DELETE)->only(['destroy']);


    }

    /**
     * Display a listing of the resource.
     * @return Response
     */
    public function index(Request $request)
    {
        return $this->companyBranch->warehouse_parts()
            ->where(function (Builder $builder) use ($request) {
                if($request->filled('name')) {
                    $builder->where('warehouse_parts.name', 'like', "%{$request->name}%")
                        ->orWhere('warehouse_parts.vendor_code', 'like', "%{$request->name}%")
                        ->orWhere('company_branches_warehouse_parts.name', 'like', "%{$request->name}%")
                        ->orWhere('company_branches_warehouse_parts.vendor_code', 'like', "%{$request->name}%");
                }
            })
            ->with('group')->paginate($request->per_page ?: 15);
    }


    function store(WarehousePartRequest $request)
    {
        DB::beginTransaction();

        /** @var Part $part */
        $part = Part::create([
            'name' => $request->input('name'),
            'vendor_code' => $request->input('vendor_code'),
            'brand_id' => $request->input('brand_id'),
            'group_id' => $request->input('group_id'),
            'unit_id' => $request->input('unit_id'),
            'images' => $request->input('images'),
        ]);

        foreach ($request->input('models') as $item) {
            $part->models()->syncWithoutDetaching([
                $item['model_id'] => [
                    'serial_numbers' => $item['serial_numbers'] ?? null
                ]
            ]);
        }

        if ($request->filled('analogue_id')) {
            $analogue = Part::query()->findOrFail($request->input('analogue_id'));
            $group = $analogue->getAnalogueGroup();
            $part->setAnalogue($group);
        }

        $this->companyBranch->warehouse_parts()->syncWithoutDetaching([$part->id]);

        DB::commit();

        return response()->json($part);
    }

    function getAvailableParts(Request $request)
    {
        return response()->json(Item::getParts());
    }

    /**
     * Store a newly created resource in storage.
     * @param  Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function addToDirectory(Request $request)
    {
        $ids = $request->all();

        $this->companyBranch->warehouse_parts()->syncWithoutDetaching($ids);

        return response()->json();
    }

    function removeFromDirectory(Request $request)
    {
        $this->companyBranch->warehouse_parts()->detach($request->all());

        return response()->json();
    }

    /**
     * Show the specified resource.
     * @param  int  $id
     * @return Response
     */
    public function show($id)
    {
        return view('partswarehouse::show');
    }

    /**
     * Update the specified resource in storage.
     * @param  Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, $id)
    {
        $toPenny = true;
        try {
            DB::beginTransaction();
            $this->companyBranch->warehouse_parts()->updateExistingPivot($id, [
                'min_order' => $request->input('min_order'),
                'min_order_type' => $request->input('min_order_type'),
                'change_hour' => $request->input('change_hour'),
                'currency' => $request->input('currency'),
                'tariff_type' => $request->input('tariff_type'),
                'is_rented' => $request->input('is_rented'),
                'is_for_sale' => $request->input('is_for_sale'),
                'name' => $request->input('name'),
                'vendor_code' => $request->input('vendor_code'),
                'default_sale_cost' => numberToPenny($request->input('default_sale_cost') ?: 0),
                'default_sale_cost_cashless' => numberToPenny($request->input('default_sale_cost_cashless')?: 0),
                'default_sale_cost_cashless_vat' => numberToPenny($request->input('default_sale_cost_cashless_vat')?: 0),
            ]);

            $id = app(RequestBranch::class)->companyBranch->warehouse_parts()->where('part_id', $id)->first()->pivot->id;

            $this->setPrices($request->input('prices'), $id, TariffGrid::WITHOUT_DRIVER);
            if ($request->filled('driver_prices')) {
                $this->setPrices($request->input('driver_prices'), $id, TariffGrid::WITH_DRIVER);
            }

            DB::commit();
        } catch (\Exception $exception) {
            return response()->json(['error' => $exception->getMessage()]);
        }

        return response()->json();
    }

    /**
     * Remove the specified resource from storage.
     * @param  int  $id
     * @return Response
     */
    public function destroy($id)
    {
        //
    }

    function searchPart(Request $request)
    {
        $request->validate([
            'vendor_code' => 'nullable|string|max:255',
            'name' => 'nullable|string|max:255',
        ]);

        $parts = Part::query()->with('group')
            ->where(function ($q) use ($request) {

                if ($request->filled('vendor_code')) {
                    $q->where('vendor_code', 'like', "%{$request->input('vendor_code')}%");
                }

                if ($request->filled('name')) {
                    $q->where('name', 'like', "%{$request->input('name')}%");
                }

            })
            ->get();

        return $parts;
    }

    function getGroups()
    {
        $groups = PartsGroup::query()->with('children')->whereNull('parent_id');

        return $groups->get();
    }

    function getParts(Request $request)
    {
        $request->validate([
            'group_id' => 'required|exists:warehouse_parts_groups,id'
        ]);

        return Part::query()->where('group_id', $request->input('group_id'))->get();

    }

    function addPartHelper()
    {
        $types = Type::all();
        $types = Type::setLocaleNames($types);
        $brands = Brand::all();
        $units = Unit::all();
        $parts = $this->companyBranch->warehouse_parts()->get();
        $stocks = Stock::query()->whereNull('parent_id')->forBranch()->get();
        $providers = PartsProvider::query()->forBranch()->get();
        $units_compares = TariffUnitCompare::forBranch()->get();

        return \response()->json([
            'providers' => $providers,
            'stocks' => $stocks,
            'parts' => $parts,
            'types' => $types,
            'brands' => $brands,
            'units' => $units,
            'units_compares' => $units_compares
        ]);
    }

    /**
     * @param  Request  $request
     * @param  int  $id
     * @param  bool  $toPenny
     * @return mixed
     */
    private function setPrices(array $prices, int $id, string $type, bool $toPenny = true)
    {
        $ids = [];
        foreach ($prices as $price) {

            $current = TariffGrid::query()->updateOrCreate([
                'unit_compare_id' => $price['unit_compare_id'],
                'min' => $price['min'],
                'type' => $type,
                'machinery_id' => $id,
                'machinery_type' => CompanyBranchWarehousePart::class,
            ], [
                'market_markup' => $price['market_markup'] ?? 0,
                'is_fixed' => toBool($price['is_fixed']),
                'sort_order' => 0,
            ]);

            $ids[] = $current->id;

            foreach ($price['grid_prices'] as $key => $value) {

                if (!$value) {
                    $value = 0;
                }

                $gridPrice = $current->gridPrices()->where('price_type', $key)->first();

                $gridPrice
                    ? $gridPrice->update([
                    'price' => $toPenny
                        ? numberToPenny($value)
                        : $value,
                ])
                    :
                    $current->gridPrices()->save(new TariffGridPrice([
                        'price' => $toPenny
                            ? numberToPenny($value)
                            : $value,
                        'price_type' => $key,
                    ]));

            }
        }
        $cbwp = CompanyBranchWarehousePart::query()->findOrFail($id);
        $cbwp->prices()->where('type', $type)->whereNotIn('id', $ids)->delete();
        return $price;
    }
}
