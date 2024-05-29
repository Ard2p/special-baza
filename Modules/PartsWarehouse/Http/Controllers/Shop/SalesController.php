<?php

namespace Modules\PartsWarehouse\Http\Controllers\Shop;

use App\Service\RequestBranch;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Modules\AdminOffice\Entities\Filter;
use Modules\CompanyOffice\Entities\Company\CompanyBranch;
use Modules\CompanyOffice\Services\CompanyRoles;
use Modules\ContractorOffice\Entities\Vehicle\Price;
use Modules\Dispatcher\Entities\DispatcherInvoice;
use Modules\Dispatcher\Entities\DispatcherInvoiceLeadPivot;
use Modules\Dispatcher\Entities\InvoiceItem;
use Modules\Orders\Entities\Order;
use Modules\PartsWarehouse\Entities\Shop\Parts\PartsRequest;
use Modules\PartsWarehouse\Entities\Shop\Parts\PartsSale;
use Modules\PartsWarehouse\Entities\Stock\Item;
use Modules\PartsWarehouse\Transformers\Shop\PartsSaleResource;

class SalesController extends Controller
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
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     */
    public function index(Request $request)
    {
        $items = PartsSale::query()
            ->withAmount($request->part_id ?: null)
            ->withCost($request->part_id ?: null)
            ->withPaidInvoiceSum()
            ->orderBy('id', 'desc')
            ->forBranch()->with('items');

        if($request->filled('part_id')) {
            $items->whereHas('items', function (Builder $q) use ($request) {
                $q->where('part_id', $request->input('part_id'));
            });

            return  PartsSaleResource::collection($items->get());
        }

        $filter = new Filter($items);
        $filter->getEqual([
            'customer_id' => 'customer_id',
            'category_id' => 'category_id',
            'base_id' => 'base_id',
            'creator_id' => 'creator_id',
        ])->getLike([
            'internal_number' => 'internal_number',
            'created_at' => 'created_at',
        ]);


        if ($request->filled('pay_type')) {

            switch ($request->input('pay_type')) {
                case 'paid':
                    $items->whereHas('invoices', function (Builder $q) {
                        $q->havingRaw('SUM(paid_sum) >= SUM(sum) ');
                    });
                    break;
                case 'partial':
                case 'not_paid':
                $items->where(function ($query) {
                        $query->whereHas('invoices', function (Builder $q) {
                            $q->havingRaw('SUM(paid_sum) < SUM(sum) ');
                        })->orWhereDoesntHave('invoices');
                    });
            }
        }



        return PartsSaleResource::collection($items->paginate($request->per_page ?: 15));
    }


    /**
     * Store a newly created resource in storage.
     * @param Request $request
     * @return Response
     */
    public function store(Request $request)
    {

    }

    /**
     * Show the specified resource.
     * @param int $id
     * @return PartsSaleResource
     */
    public function show($id)
    {
        $items = PartsSale::query()->forBranch()->withCost();

        return PartsSaleResource::make($items->findOrFail($id));
    }

    function getDocuments($id)
    {
        $items = PartsSale::query()->forBranch()->findOrFail($id);

        return $items->documents;
    }

    function invoice(Request $request, $id)
    {
        $partSale = PartsSale::query()->forBranch()->findOrFail($id);
        $request->validate([
            'parts' => 'required|array|min:1',
            'parts.*.id' => 'required',
            'parts.*.amount' => 'required|numeric|min:1',
            'parts.*.cost_per_unit' => 'required|numeric|min:1',
            'date' => 'required|date',
        ]);
        $customer = $partSale->customer;

        DB::beginTransaction();
        $sum = 0;
        $invoice = new DispatcherInvoice([
            'sum' => 0,
            'alias' => 1,
            'company_branch_id' => $this->companyBranch->id,
            'number' => "{$partSale->internal_number}-{$customer->internal_number}",
        ]);
        $invoice->customerRequisite()->associate($customer->legal_requisites
            ?: $customer->individual_requisites);
        $partSale->invoices()->save($invoice);
        foreach ($request->input('parts') as $item) {

            $component = $partSale->items()->findOrFail($item['id']);

            $sum += $item['amount'] * numberToPenny($item['cost_per_unit']);

            $position = new InvoiceItem([
                'vendor_code'   => $component->part->vendor_code,
                'cost_per_unit' =>  numberToPenny($item['cost_per_unit']),
                'amount'        => $item['amount'],
                'name'          => $component->part->name,
                'description'   => '',
                'unit'          => $component->unit->name,
            ]);

            $invoice->positions()->save($position);
        }
        $invoice->update(['sum' => $sum]);

        \DB::commit();
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'documents_pack_id' => 'required',
            'contractor_requisite_id' => 'required',
        ]);
        $partSale = PartsSale::query()->forBranch()->findOrFail($id);

        $reqData = explode('_', $request->input('contractor_requisite_id'));
        if ($req = $partSale->company_branch->findRequisiteByType($reqData[1], $reqData[0])) {
            $partSale->contractorRequisite()->associate($req);
        }

        $partSale->documents_pack_id = $request->input('documents_pack_id');
        $partSale->save();
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
