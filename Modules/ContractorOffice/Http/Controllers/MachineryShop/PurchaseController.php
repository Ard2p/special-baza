<?php

namespace Modules\ContractorOffice\Http\Controllers\MachineryShop;

use App\Service\RequestBranch;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\CompanyOffice\Services\CompanyRoles;
use Modules\ContractorOffice\Entities\System\Tariff;
use Modules\ContractorOffice\Entities\Vehicle\Price;
use Modules\ContractorOffice\Entities\Vehicle\Shop\MachineryPurchase;
use Modules\ContractorOffice\Entities\Vehicle\Shop\OperationCharacteristic;
use Modules\ContractorOffice\Http\Requests\Shop\MachineryPurchaseRequest;
use Modules\ContractorOffice\Services\VehicleService;
use Modules\PartsWarehouse\Entities\PartsProvider;

class PurchaseController extends Controller
{

    private $companyBranch;

    public function __construct(Request $request, RequestBranch $companyBranch)
    {
        $this->companyBranch = $companyBranch->companyBranch;

        if(!$this->companyBranch && Str::contains($request->route()->getActionName(), 'getModels')) {
            return;
        }

        $block = $this->companyBranch->getBlockName(CompanyRoles::BRANCH_PROPOSALS);

        $this->middleware("accessCheck:{$block}," . CompanyRoles::ACTION_SHOW)->only('index', 'show');
        $this->middleware("accessCheck:{$block}," . CompanyRoles::ACTION_CREATE)->only(['store', 'update',]);
        $this->middleware("accessCheck:{$block}," . CompanyRoles::ACTION_DELETE)->only(['destroy']);
    }

    /**
     * Display a listing of the resource.
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function index(Request $request)
    {

        /** @var Builder $purchases */
        $purchases  = MachineryPurchase::query()->with('provider')->forBranch();
        $purchases->orderBy('created_at', 'desc');

        return $purchases->paginate($request->per_page ?: 15);
    }


    /**
     * Store a newly created resource in storage.
     * @param Request $request
     * @return Response
     */
    public function store(MachineryPurchaseRequest $request)
    {
        DB::beginTransaction();

        $provider = PartsProvider::query()->forBranch()->findOrFail($request->input('provider_id'));

        /** @var MachineryPurchase $purchase */
        $purchase = MachineryPurchase::create([
            'pay_type' => $request->input('pay_type'),
            'account_number' => $request->input('account_number'),
            'account_date' => $request->input('account_date'),
            'provider_id' => $provider->id,
            'currency' => $request->input('currency'),
            'creator_id' => \Auth::id(),
            'company_branch_id' => $this->companyBranch->id,
        ]);


        foreach ($request->input('machineries') as $machinery)
        {
            $mService = new VehicleService($this->companyBranch);

            $prices = [];
            foreach (Price::getTypes() as $type) {
                $prices[] = [
                    'cost_per_shift' => 0,
                    'cost_per_hour' => 0,
                    'type' => $type,
                ];
            }
            $mService->setData([
                'region_id' => $this->companyBranch->region_id,
                'city_id' => $this->companyBranch->city_id,
                 'category_id' => $machinery['category_id'],
                 'brand_id' => $machinery['brand_id'],
                 'model_id' => $machinery['model_id'],
                 'vin' => $machinery['vin'] ?? null,
                 'name' => $machinery['name'] ?? '',
                 'serial_number' => $machinery['serial_number'] ?? null,
                 'licence_plate' => $machinery['licence_plate'] ?? null,
                 'board_number' => $machinery['board_number'] ?? null,
                 'tariff_type' => Tariff::TIME_CALCULATION,
                 'address' => '',
                 'description' => '',
                 'shift_duration' => 8,
                 'coordinates' => false,
                 'delivery_radius' => 50,
                 'delivery_cost_over' => 0,
                 'is_rented' => false,
                 'is_rented_in_market' => false,
                 'price_includes_fas' => false,
                 'is_contractual_delivery' => false,
                 'change_hour' => 24,
                 'min_order' => 1,
                 'photo' => [],
                 'scans' => [],
                 'prices' => $prices,

            ]);
            $newMachine = $mService->createVehicle();
            $operation = new OperationCharacteristic([
                'machinery_id' => $newMachine->id,
                'cost' => numberToPenny($machinery['cost']),
                'engine_hours' => $machinery['engine_hours'] ?? 0,
                'type' => $machinery['type'],
            ]);

            $purchase->operations()->save($operation);
        }
        DB::commit();

        return $purchase;
    }

    /**
     * Show the specified resource.
     * @param int $id
     * @return Response
     */
    public function show($id)
    {
        return view('contractoroffice::show');
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
}
