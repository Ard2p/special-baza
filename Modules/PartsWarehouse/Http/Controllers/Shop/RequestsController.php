<?php

namespace Modules\PartsWarehouse\Http\Controllers\Shop;

use App\Helpers\RequestHelper;
use App\Service\RequestBranch;
use App\User\IndividualRequisite;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Modules\CompanyOffice\Entities\Company\CompanyBranch;
use Modules\CompanyOffice\Services\CompanyRoles;
use Modules\ContractorOffice\Entities\Vehicle\Price;
use Modules\Dispatcher\Entities\Customer;
use Modules\PartsWarehouse\Entities\Posting;
use Modules\PartsWarehouse\Entities\Shop\Parts\PartsRequest;
use Modules\PartsWarehouse\Entities\Shop\Parts\PartsRequestPosition;
use Modules\PartsWarehouse\Entities\Shop\Parts\PartsSale;
use Modules\PartsWarehouse\Entities\Stock\Item;
use Modules\PartsWarehouse\Entities\Stock\ItemSerial;
use Modules\PartsWarehouse\Entities\Warehouse\Part;
use Modules\PartsWarehouse\Http\Requests\FastOrderSaleRequest;
use Modules\PartsWarehouse\Transformers\Shop\PartsRequestResource;


class RequestsController extends Controller
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
            'sale',
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
        $items = PartsRequest::query()->forBranch()->with('positions');

        return response()->json($items->orderBy('id', 'desc')->paginate($request->per_page ?: 15));
    }


    /**
     * Store a newly created resource in storage.
     * @param Request $request
     * @return Response
     */
    public function store(Request $request)
    {
        $request->validate([
            'date' => 'required|date',
            'customer_id' => 'nullable|exists:dispatcher_customers,id',
            'phone' => 'required|numeric|digits:' .RequestHelper::requestDomain()->options['phone_digits'],
            'pay_type' => 'required|in:' . implode(',', Price::getTypes()),
            'email' => 'nullable|email|max:255',
            'contact_person' => 'required|string|max:255',
            'positions' => 'required|array',
            'positions.*.part_id' => 'required|exists:warehouse_parts,id',
            'positions.*.amount' => 'required|int|min:1|max:99999999',
        ]);

        DB::beginTransaction();

        if ($request->filled('customer_id')) {
            Customer::query()->forBranch()->findOrFail($request->input('customer_id'));
        }
        $partsRequest = PartsRequest::create([
            'date' => $request->input('date'),
            'customer_id' => $request->input('customer_id'),
            'phone' => $request->input('phone'),
            'pay_type' => $request->input('pay_type'),
            'email' => $request->input('email'),
            'contact_person' => $request->input('contact_person'),
            'company_branch_id' => $this->companyBranch->id,
        ]);

        foreach ($request->input('positions') as $position) {

            PartsRequestPosition::create([
                'part_id' => $position['part_id'],
                'parts_request_id' => $partsRequest->id,
                'amount' => $position['amount'],
                'cost_per_unit' => 0,
            ]);
        }
        DB::commit();

        return response()->json($partsRequest);
    }

    /**
     * Show the specified resource.
     * @param int $id
     * @return Response
     */
    public function show($id)
    {
        $sale = PartsRequest::query()->forBranch()->findOrFail($id);

        return PartsRequestResource::make($sale);
    }


    /**
     * Update the specified resource in storage.
     * @param Request $request
     * @param int $id
     * @return Response
     */
    public function update(Request $request, $id)
    {
        $request->validate([
            'date' => 'required|date',
            'customer_id' => 'nullable|exists:dispatcher_customers,id',
            'phone' => 'required|numeric|digits:' .RequestHelper::requestDomain()->options['phone_digits'],
            'pay_type' => 'required|in:' . implode(',', Price::getTypes()),
            'email' => 'nullable|email|max:255',
            'contact_person' => 'required|string|max:255',
            'positions' => 'required|array',
            'positions.*.part_id' => 'required|exists:warehouse_parts,id',
            'positions.*.amount' => 'required|int|min:1|max:99999999',
        ]);

        /** @var PartsRequest $partsRequest */
        $partsRequest = PartsRequest::query()->forBranch()->findOrFail($id);

        DB::beginTransaction();

        if ($request->filled('customer_id')) {
            Customer::query()->forBranch()->findOrFail($request->input('customer_id'));
        }
        $partsRequest->update([
            'date' => $request->input('date'),
            'customer_id' => $request->input('customer_id'),
            'phone' => $request->input('phone'),
            'pay_type' => $request->input('pay_type'),
            'email' => $request->input('email'),
            'contact_person' => $request->input('contact_person'),
        ]);
        $partsRequest->positions()->delete();

        foreach ($request->input('positions') as $position) {

            PartsRequestPosition::create([
                'part_id' => $position['part_id'],
                'parts_request_id' => $partsRequest->id,
                'amount' => $position['amount'],
                'cost_per_unit' => 0,
            ]);
        }
        DB::commit();

        return response()->json($partsRequest);
    }

    function sale(Request $request, $id)
    {
        $partRequest = PartsRequest::query()->forBranch()->findOrFail($id);

        $request->validate([
            'cart' => 'required|array',
            'cart.*.id' => [
                'required',
                'distinct',
                Rule::exists('stock_items', 'id')->where('company_branch_id', $this->companyBranch->id)
            ],
            'cart.*.amount' => 'required|numeric|min:1',
            'cart.*.serials' => 'nullable|array',
            'cart.*.cost_per_unit' => 'required|numeric|min:0',
        ]);

        DB::beginTransaction();

        $sale = PartsSale::create([
            'status' => 'open',
            'source' => 'transbaza',
            'date' => $partRequest->date,
            'parts_request_id' => $partRequest->id,
            'customer_id' => $partRequest->customer_id,
            'creator_id' => Auth::id(),
            'company_branch_id' => $this->companyBranch->id,
        ]);

        foreach ($request->input('cart') as $cartItem) {
            /** @var Item $availableItem */
            $availableItem = Item::query()->whereHasMorph('owner', [Posting::class])->forBranch()->findOrFail($cartItem['id']);
            if ($cartItem['amount'] > $availableItem->available_amount) {
                $error = ValidationException::withMessages([
                    'errors' => ["{$availableItem->part->name} указано больше чем имеется на складе."]
                ]);

                throw $error;
            }
            /** @var Item $item */
            $item = new Item([
                'part_id' => $availableItem->part_id,
                'stock_id' => $availableItem->stock_id,
                'unit_id' => $availableItem->unit_id,
                'cost_per_unit' => numberToPenny($cartItem['cost_per_unit']),
                'amount' => $availableItem->serial_accounting ? 0 : $cartItem['amount'],
                'serial_accounting' => $availableItem->serial_accounting,
                'company_branch_id' => $this->companyBranch->id,
            ]);
            $item->owner()->associate($sale);
            $item->save();

            if ($item->serial_accounting) {

                foreach ($cartItem['serials'] as $id) {

                    $num = $availableItem->serialNumbers()->find($id);
                    if ($num) {
                        $newItemSerial = $item->serialNumbers()->save(new ItemSerial(['serial' => $num->serial]));
                        $num->update([
                            'item_sale_id' => $newItemSerial->id
                        ]);
                    }
                }
                $item->update([
                    'amount' => $item->serialNumbers()->count()
                ]);
            }
        }

        DB::commit();

        return response()->json($sale);
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

    function fastOrder(FastOrderSaleRequest $request)
    {

        DB::beginTransaction();

        if ($request->filled('customer_id')) {
            $customer = Customer::forCompany()->findOrFail($request->input('customer_id'));
        } else {
            $customer = Customer::create([
                'email' => $request->input('email'),
                'company_name' => $request->input('customer.company_name'),
                'region_id' => $request->input('customer.region_id'),
                'city_id' => $request->input('customer.city_id'),
                'contact_person' => $request->input('contact_person'),
                'phone' => $request->input('phone'),
                'creator_id' => Auth::id(),
                'company_branch_id' => $this->companyBranch->id,
                'domain_id' => RequestHelper::requestDomain()->id,
            ]);

            $requisite = $request->input('customer.requisite');
            $requisite['creator_id'] = Auth::id();
            $requisite['company_branch_id'] = $this->companyBranch->id;

            if ($request->input('has_requisite') === 'legal') {

                $customer->addLegalRequisites($requisite);
            }

            if (in_array($request->input('has_requisite'), [
                IndividualRequisite::TYPE_PERSON,
                IndividualRequisite::TYPE_ENTREPRENEUR,
            ])) {

                $customer->addIndividualRequisites($requisite);
            }
        }

        $partsRequest = PartsRequest::create([
            'date' => $request->input('date'),
            'customer_id' => $customer->id,
            'phone' => $request->input('phone'),
            'pay_type' => $request->input('pay_type'),
            'email' => $request->input('email'),
            'contact_person' => $request->input('contact_person'),
            'company_branch_id' => $this->companyBranch->id,
        ]);

        $sale = PartsSale::create([
            'status' => 'open',
            'source' => 'transbaza',
            'date' => $partsRequest->date,
            'parts_request_id' => $partsRequest->id,
            'customer_id' => $customer->id,
            'creator_id' => $request->input('creator_id'),
            'title' => $request->input('title'),
            'external_id' => $request->input('external_id'),
            'documents_pack_id' => $request->input('documents_pack_id'),
            'base_id' => $request->input('base_id'),

            'company_branch_id' => $this->companyBranch->id,
        ]);
        if ($request->input('contractor_requisite_id')) {

            $reqData = explode('_', $request->input('contractor_requisite_id'));
            if ($req = $sale->company_branch->findRequisiteByType($reqData[1], $reqData[0])) {
                $sale->contractorRequisite()->associate($req);
                $sale->save();
            }
        }
        foreach ($request->input('positions') as $cartItem) {
            /** @var Item $availableItem */
            $availableItem = Item::query()->whereHasMorph('owner', [Posting::class])->forBranch()->findOrFail($cartItem['id']);
            if ($cartItem['amount'] > $availableItem->available_amount) {
                $error = ValidationException::withMessages([
                    'errors' => ["{$availableItem->part->name} указано больше чем имеется на складе."]
                ]);

                throw $error;
            }
            /** @var Item $item */
            $item = new Item([
                'part_id' => $availableItem->part_id,
                'stock_id' => $availableItem->stock_id,
                'unit_id' => $availableItem->unit_id,
                'cost_per_unit' => numberToPenny($cartItem['cost_per_unit']),
                'amount' => $availableItem->serial_accounting ? 0 : $cartItem['amount'],
                'serial_accounting' => $availableItem->serial_accounting,
                'company_branch_id' => $this->companyBranch->id,
            ]);
            $item->owner()->associate($sale);
            $item->save();

            if ($item->serial_accounting) {

                foreach ($cartItem['serials'] as $id) {

                    $num = $availableItem->serialNumbers()->find($id);
                    if ($num) {
                        $newItemSerial = $item->serialNumbers()->save(new ItemSerial(['serial' => $num->serial]));
                        $num->update([
                            'item_sale_id' => $newItemSerial->id
                        ]);
                    }
                }
                $item->update([
                    'amount' => $item->serialNumbers()->count()
                ]);
            }
        }

        DB::commit();

        return response()->json($sale);
    }

}
