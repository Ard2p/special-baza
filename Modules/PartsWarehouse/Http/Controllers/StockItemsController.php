<?php

namespace Modules\PartsWarehouse\Http\Controllers;

use App\Machines\MachineryModel;
use App\Machines\Sale;
use App\Overrides\Model;
use App\Service\RequestBranch;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Modules\CompanyOffice\Entities\Company\CompanyBranch;
use Modules\CompanyOffice\Services\CompanyRoles;
use Modules\Orders\Entities\OrderComponent;
use Modules\Orders\Entities\Service\ServiceCenter;
use Modules\PartsWarehouse\Entities\PartsProvider;
use Modules\PartsWarehouse\Entities\Posting;
use Modules\PartsWarehouse\Entities\Shop\Parts\PartsSale;
use Modules\PartsWarehouse\Entities\Stock\Item;
use Modules\PartsWarehouse\Entities\Stock\ItemSerial;
use Modules\PartsWarehouse\Entities\Stock\Stock;
use Modules\PartsWarehouse\Entities\Warehouse\Part;
use Modules\PartsWarehouse\Http\Requests\StockItemRequest;
use Modules\PartsWarehouse\Services\RentService;
use Modules\PartsWarehouse\Transformers\RentPartResource;
use Modules\PartsWarehouse\Transformers\StockItemCard;
use Modules\PartsWarehouse\Transformers\StockItemResource;
use function Clue\StreamFilter\fun;

class StockItemsController extends Controller
{

    /** @var CompanyBranch */
    private $companyBranch;

    public function __construct(
        Request       $request,
        RequestBranch $companyBranch)
    {
        $this->companyBranch = $companyBranch->companyBranch;
        $block = $this->companyBranch->getBlockName(CompanyRoles::BRANCH_PROPOSALS);
        $this->middleware("accessCheck:{$block}," . CompanyRoles::ACTION_SHOW)->only([
            'index', 'show',
            'getAvailableStocks'
        ]);

        $this->middleware("accessCheck:{$block}," . CompanyRoles::ACTION_CREATE)->only([
            'store',
            'update',

        ]);

        $this->middleware("accessCheck:{$block}," . CompanyRoles::ACTION_DELETE)->only(['destroy']);


    }

    /**
     * Display a listing of the resource.
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     */
    public function index(Request $request)
    {
        //$items = Item::query()->groupBy('part_id')->forBranch();
        $items = $this->companyBranch->warehouse_parts()
            ->where(function (Builder $builder) use
            (
                $request
            ) {
                if ($request->filled('name')) {
                    $builder->where('warehouse_parts.name', 'like', "%{$request->name}%")
                        ->orWhere('warehouse_parts.vendor_code', 'like', "%{$request->name}%")
                        ->orWhere('company_branches_warehouse_parts.name', 'like', "%{$request->name}%")
                        ->orWhere('company_branches_warehouse_parts.vendor_code', 'like', "%{$request->name}%");
                }
            })
            ->with('group');

        if($request->boolean('is_rented')){
            $items->wherePivot('is_rented', true);
        }
        if($request->boolean('is_for_sale')) {
            $items->wherePivot('is_for_sale', true);
        }
        if($request->filled('model_ids')) {
            $items->whereRelation('models', fn(Builder $builder) => $builder->whereIn($builder->qualifyColumn('id'), $request->input('model_ids')));
        }
        $items =
            $request->filled('noPagination')
                ? $items->get()
                : $items->paginate($request->per_page
                ?: 15);
        $collection = StockItemResource::collection($items);

        $collection->additional([
            'models' => MachineryModel::query()->whereHas('parts', function (Builder $builder) {
              $builder->whereRelation('company_branches','company_branches.id', $this->companyBranch->id) ;
            })->get()
        ]);

        return $collection;
    }

    function getAvailableItems()
    {
        return Item::getParts();
    }

    function getAvailableRentItems(Request $request)
    {
        $rentService = new RentService();
        $dateFrom = Carbon::parse($request->input('date_from'));
        $orderDuration = $request->input('order_duration');


        return RentPartResource::collection(
            $rentService->getRentPartsCountForPeriod($dateFrom->format('Y-m-d'),
                $dateFrom->addDays($orderDuration)->format('Y-m-d'),
                null,
                $request->input('stock_id'),
                $request->input('name'),
                $request->input('vendor_code'),
                $request->input('brand_id')
            )
        );
    }


    /**
     * Store a newly created resource in storage.
     * @param StockItemRequest $request
     * @return \Illuminate\Http\JsonResponse
     * @throws \Exception
     */
    public function store(StockItemRequest $request)
    {


        DB::beginTransaction();

        $provider = PartsProvider::query()->forBranch()->findOrFail($request->input('provider_id'));

        $posting = Posting::create([
            'parts_provider_id' => $provider->id,
            'pay_type'          => $request->input('pay_type'),
            'date'              => $request->input('date'),
            'account_number'    => $request->input('account_number'),
            'account_date'      => $request->input('account_date'),
            'company_branch_id' => $this->companyBranch->id,
        ]);
        foreach ($request->input('parts') as $part) {

            $stock = Stock::query()->forBranch()->findOrFail($part['stock_id']);
            $serialAccounting = toBool($part['serial'] ?? false);

            /** @var Item $item */
            $item = new Item([
                'part_id'           => $part['id'],
                'stock_id'          => $stock->id,
                'unit_id'           => $part['unit_id'],
                'cost_per_unit'     => numberToPenny($part['cost_per_unit']),
                'amount'            => $serialAccounting
                    ? 0
                    : $part['amount'],
                'serial_accounting' => $serialAccounting,
                'company_branch_id' => $this->companyBranch->id,
            ]);
            $item->owner()->associate($posting);
            $item->save();
            if ($serialAccounting) {

                foreach ($part['serial_numbers'] as $number) {
                    $item->serialNumbers()->save(new ItemSerial(['serial' => $number['serial']]));
                }
                $item->update([
                    'amount' => $item->serialNumbers()->count()
                ]);
            }

        }

        DB::commit();
        return response()->json($item);
    }

    function getAvailableStocks($id)
    {
        /** @var Builder $items */
        $items = Item::query()->forBranch();
        $items = $items->where('part_id', $id)->groupBy('stock_id')->get();
        $items = $items->map(function ($item) {
            $item->available_amount = $item->getSameCount(true);
            return $item;
        })->filter(function ($item) {
            return $item->available_amount > 0;
        });
        /*  ->selectRaw("*,
          SUM(case when owner_type = '{$postingOwner}' then amount else 0 end) as postingAmount,
          SUM(case when owner_type = '{$salesOwner}' then amount else 0 end) as salesAmount,

          ")->havingRaw('(postingAmount - salesAmount) > 0')*/

        return $items;
    }

    /**
     * Show the specified resource.
     * @param int $id
     * @return StockItemCard
     */
    public function show($id)
    {
        return StockItemCard::make($this->companyBranch->warehouse_parts()->with('group')->findOrFail($id));
    }

    public function updateField(
        Request $request,
                $id)
    {
        $fields = [
            'is_for_sale',
            'is_rented',
            'default_sale_cost',
            'default_sale_cost_cashless',
            'default_sale_cost_cashless_vat',
        ];
        $request->validate([
            'is_for_sale' => 'nullable|boolean',
            'is_rented'   => 'nullable|boolean',
        ]);
        $part = $this->companyBranch->warehouse_parts()->findOrFail($id);

        foreach ($fields as $field) {
            if ($request->filled($field)) {
                $this->companyBranch->warehouse_parts()->updateExistingPivot($id, [
                    $field => $request->input($field),
                ]);
            }
        }
        $part->save();
    }

    /**
     * Update the specified resource in storage.
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(
        Request $request,
                $id)
    {
        Item::query()->where('id', $id)->update([
            'min_order'      => $request->input('min_order'),
            'min_order_type' => $request->input(),
            'change_hour'    => $request->input(),
            'currency'       => $request->input(),
            'tariff_type'    => $request->input(),
        ]);

        return response()->json(Item::query()->findOrFail($id));
    }

    function getPartsConsumption(
        Request $request,
                $id)
    {
        $items = Item::query()->with('owner')->forBranch()
            ->where('part_id', $id)->where('owner_type', '!=', Posting::class);

        return $items->get()->map(function (Item $item) {
            if($item->owner_type) {
                $item->owner = (new $item->owner_type())->newQuery()->find($item->owner_id);
            }
            if($item->owner instanceof ServiceCenter) {
                $item->owner->load('machinery');
            }
            $item->type = match ($item->owner ? get_class($item->owner) : null) {
                    OrderComponent::class => 'order',
                    ServiceCenter::class => 'service',
                    PartsSale::class => 'sale',
                    default => null
                };
            if ($item->type) {
                if ($item->owner instanceof OrderComponent) {
                    $item->customer = $item->owner->order->customer;
                    $item->date = $item->owner->date_from;
                } else {
                    $item->customer = $item->owner?->customer;
                    $item->date = $item->owner?->date ?: $item->owner?->date_from;
                }
            }

            return $item;
        });
    }

    /**
     * Remove the specified resource from storage.
     * @param int $id
     * @return void
     */
    public function destroy($id)
    {
        //
        return;
    }
}
