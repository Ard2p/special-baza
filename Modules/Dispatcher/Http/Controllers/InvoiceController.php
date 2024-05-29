<?php

namespace Modules\Dispatcher\Http\Controllers;

use App\Machinery;
use App\Service\RequestBranch;
use App\User\EntityRequisite;
use App\User\IndividualRequisite;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Modules\CompanyOffice\Entities\Company\CompanyBranch;
use Modules\CompanyOffice\Entities\Company\CompanyBranchSettings;
use Modules\CompanyOffice\Services\CompanyRoles;
use Modules\ContractorOffice\Entities\Services\CustomService;
use Modules\ContractorOffice\Entities\System\TariffUnitCompare;
use Modules\ContractorOffice\Entities\Vehicle\Price;
use Modules\ContractorOffice\Services\Tariffs\TimeCalculation;
use Modules\CorpCustomer\Entities\InternationalLegalDetails;
use Modules\Dispatcher\Entities\Customer;
use Modules\Dispatcher\Entities\Directories\Contractor;
use Modules\Dispatcher\Entities\DispatcherInvoice;
use Modules\Dispatcher\Entities\DispatcherInvoiceDepositTransfer;
use Modules\Dispatcher\Entities\DispatcherOrder;
use Modules\Dispatcher\Entities\InvoiceItem;
use Modules\Dispatcher\Entities\Lead;
use Modules\Dispatcher\Http\Requests\DispatcherOrderInvoiceRequest;
use Modules\Integrations\Services\OneC\OneCService;
use Modules\Orders\Entities\Order;
use Modules\Orders\Entities\OrderComponent;
use Modules\Orders\Entities\OrderComponentService;
use Modules\Orders\Entities\Service\ServiceCenter;
use Modules\Orders\Entities\Service\ServiceWork;
use Modules\Orders\Repositories\InvoiceRepository;
use Modules\Orders\Repositories\OrderRepository;
use Modules\Orders\Services\OrderDocumentService;
use Modules\PartsWarehouse\Entities\Shop\Parts\PartsSale;
use Modules\PartsWarehouse\Entities\Stock\Item;
use Modules\PartsWarehouse\Entities\Warehouse\WarehousePartSet;
use PDF;
use function Clue\StreamFilter\fun;

class InvoiceController extends Controller
{
    /** @var Order */
    private $order;

    /** @var CompanyBranch */
    private $companyBranch;

    public function __construct(Request $request, RequestBranch $companyBranch)
    {
        $this->companyBranch = $companyBranch->companyBranch;

        $block = $this->companyBranch->getBlockName(CompanyRoles::BRANCH_PAYMENTS);

        $this->middleware("accessCheck:{$block}," . CompanyRoles::ACTION_SHOW)->only([
            'index',
        ]);
        $this->middleware("accessCheck:{$block}," . CompanyRoles::ACTION_CREATE)->only(['store']);

        if ($request->filled('owner_id')) {

            switch ($request->input('owner_type')) {
                case 'order':
                    $this->order = Order::forBranch($this->companyBranch->id)->findOrFail($request->input('owner_id'));
                    break;
                case 'partSale':
                    $this->order =
                        PartsSale::forBranch($this->companyBranch->id)->findOrFail($request->input('owner_id'));
                    break;
                case 'lead':
                    $this->order = Lead::forBranch($this->companyBranch->id)->findOrFail($request->input('owner_id'));
                    break;
                case 'service':
                    $this->order =
                        ServiceCenter::query()->forBranch($this->companyBranch->id)->findOrFail($request->input('owner_id'));
                    break;
            }
        }


    }

    public function index()
    {
        $invoices = $this->order->invoices;
        return $invoices->map(function ($item) {
            $item->refresh();
            $item->load('receivingFromDonor', 'pays', 'positions');
            $item->setAppends([
                'application_id',
                'order_data',
                'one_c_info',
                'link',
                'paid',
            ]);
            return $item;
        })->sortBy('number')->values()->all();
    }


    function getNestedServices(
        $items,
        $isVatSystem)
    {
        $services = [];
        foreach ($items->unique('id') as $item) {
            if (!empty($item['services'])) {
                foreach ($item['services'] as $service) {
                    if ($service['custom_price'] > 0) {
                        $service['custom_price'] =
                            Price::addVat(numberToPenny($service['custom_price']), $isVatSystem
                                ? $this->companyBranch->domain->vat
                                : 0);

                        $service['customService'] =
                            CustomService::query()->forBranch()->findOrFail($service['custom_service_id']);
                        $services[] = (object)$service;

                    }
                }
            }
        }

        return collect($services);
    }

    function addServiceInvoice(Request $request)
    {
        $request->validate([
            'parts' => 'nullable|array',
            'parts.*.amount' => 'required|numeric|min:1',
            'parts.*.cost_per_unit' => 'required|numeric|min:1',
            'works.*.amount' => 'required|numeric|min:1',
            'works.*.cost_per_unit' => 'required|numeric|min:1',
            'works' => 'nullable|array',
        ]);
        /** @var ServiceCenter $service */
        $service = ServiceCenter::query()->forBranch()->findOrFail($request->input('id'));
        \DB::beginTransaction();

        $invoice = new DispatcherInvoice([
            'sum' => 0,
            'alias' => 1,
            'is_black' => toBool($request->input('is_black')),
            'company_branch_id' => $this->companyBranch->id,
            'number' => "{$service->internal_number}-{$service->customer->internal_number}-" . $service->invoices()->count(),
        ]);

        $invoice->owner()->associate($service);

        $invoice->customerRequisite()->associate($service->customer instanceof CompanyBranch ? $service->contractorRequisite : $service->customer->getRequisites());
        $invoice->save();
        foreach ($request->input('parts') as $part) {
            $position = new InvoiceItem([
                'vendor_code' => $part['part']['vendor_code'],
                'cost_per_unit' => numberToPenny($part['cost_per_unit']),
                'amount' => $part['amount'],
                'name' => $part['name'],
                'description' => '',
                'unit' => $part['unit']['name']
            ]);
            $part = $service->parts()->findOrFail($part['id']);
            $position->owner()->associate($part);
            $invoice->positions()->save($position);
        }

        foreach ($request->input('works') as $work) {
            $position = new InvoiceItem([
                'vendor_code' => "{$work['vendor_code']}",
                'cost_per_unit' => numberToPenny($work['cost_per_unit']),
                'amount' => $work['amount'],
                'name' => $work['name'],
                'description' => '',
                'unit' => 'чел/час'
            ]);
            $work = $service->works()->findOrFail($work['id']);
            $position->owner()->associate($work);
            $invoice->positions()->save($position);
        }

        $invoice->sum = $invoice->positions()->get()->sum('sum');
        $invoice->save();
        \DB::commit();

        return $invoice;
    }

    public function invoiceForPledge(Request $request)
    {
        $request->validate([
            'date' => 'nullable|date',
            'pledge_sum' => 'required|numeric|min:0',
            'use_oneC' => 'nullable|boolean',
        ]);
        $useOneC = $request->boolean('use_oneC');

        \DB::beginTransaction();

        $invoice = new DispatcherInvoice([
            'sum' => numberToPenny($request->input('pledge_sum')),
            'alias' => 1,
            'type' => 'pledge',
            'company_branch_id' => $this->companyBranch->id,
            'number' => !$useOneC && $request->filled('invoice_number')
                ? $request->input('invoice_number')
                : "{$this->order->internal_number}-{$this->order->customer->internal_number}-" . $this->order->invoices()->count(),
        ]);

        $invoice->customerRequisite()->associate($this->order->customer->legal_requisites
            ?: $this->order->customer->individual_requisites);
        $invoice->dispatcherLegalRequisite()->associate($this->order->contractorRequisite);

        $this->order->invoices()->save($invoice);

        $position = new InvoiceItem([
            'vendor_code' => 'pledge_vendor_code',
            'cost_per_unit' => numberToPenny($request->input('pledge_sum')),
            'amount' => 1,
            'name' => 'Обеспечительный платеж.',
            'unit' => 'шт.'
        ]);
        $invoice->positions()->save($position);

        \DB::commit();
    }

    function invoiceForSet(Request $request)
    {
        $request->validate([
            'date' => 'nullable|date',
        ]);
        /** @var Customer $customer */
        $customer = $this->order->customer;
        if (!$this->order->machinerySets->count()) {
            throw ValidationException::withMessages(['Комплект отсутствует']);
        }
        \DB::beginTransaction();
        $sum = 0;
        $useOneC = toBool($request->input('use_oneC'));
        $invoice = new DispatcherInvoice([
            'sum' => $this->order->amount,
            'alias' => 1,
            'is_black' => toBool($request->input('is_black')),
            'company_branch_id' => $this->companyBranch->id,
            'number' => !$useOneC && $request->filled('invoice_number')
                ? $request->input('invoice_number')
                : "{$this->order->internal_number}-{$customer->internal_number}-" . $this->order->invoices()->count(),
        ]);
        $invoice->customerRequisite()->associate($customer->legal_requisites
            ?: $customer->individual_requisites);
        $invoice->dispatcherLegalRequisite()->associate($this->order->contractorRequisite);
        if ($request->filled('date')) {
            $invoice->timestamps = false;
            $invoice->created_at = $request->input('date', now());
        }
        $this->order->invoices()->save($invoice);

        foreach ($this->order->machinerySets as $set) {
            $position = new InvoiceItem([
                'vendor_code' => '',
                'cost_per_unit' => ($set->prices->sum ?? 0) / $set->count,
                'amount' => $set->count,
                'unit' => trans('units.count_short'),
                'name' => $set->machinerySet->name,
            ]);
            $invoice->positions()->save($position);
        }


        if ($this->companyBranch->OneCConnection && $useOneC) {
            $connection = new OneCService($this->companyBranch);
            $response =
                $connection->addInvoice($invoice->number, $invoice->id, $customer->getRequisites(), $this->order->contractorRequisite->inn, $data);


            if ($response['code'] !== 200) {
                return response()->json([
                    'errors' => $response['message']
                ], 400);
            }

            if (!empty($response['message']['Number'])) {
                $invoice->update([
                    'onec' => 1,
                    'number' => $response['message']['Number']
                ]);
            }

        }

        \DB::commit();

    }

    function store(Request $request)
    {
        if ($request->input('type') === 'pledge') {
            return $this->invoiceForPledge($request);
        }
        if (toBool($request->input('machinery_set'))) {
            return $this->invoiceForSet($request);
        }

        $request->validate([
            'partial_percent' => 'nullable|numeric|min:0|max:100',
            'type' => 'required|string|in:time_calculation,custom_calculation',
            'date' => 'nullable|date',
            'items.*.id' => 'required|exists:order_workers,id',
            'items.*.order_duration' => [
                'required',
                'numeric',
                'min:1',
            ],
            'positions.*.id' => 'required',
            'positions.*.date_from' => 'required|date',
            'positions.*.order_duration' => 'required|min:0',
            'positions.*.cost_per_unit' => 'required|numeric|min:0',
            'positions.*.delivery_cost' => 'required|numeric|min:0',
            'positions.*.return_delivery' => 'required|numeric|min:0',
        ]);
        /** @var Customer $customer */
        $customer = $this->order->customer;
        $contractorRequisite = $this->order->contractorRequisite;

        if ($request->filled('customer_id')) {
            $customer = Customer::query()->find($request->input('customer_id'));
        }
        if ($request->filled('contractor_id')) {
            $contractor = Contractor::query()->find($request->input('contractor_id'));
            $contractorRequisite = $contractor->contractorRequisite;
        }
        \DB::beginTransaction();

        $sum = 0;
        $useOneC = toBool($request->input('use_oneC'));
        $invoice = new DispatcherInvoice([
            'use_onec_naming' => $request->boolean('use_onec_naming'),
            'sum' => 0,
            'alias' => 1,
            'invoice_pay_days_count' => $request->input('invoice_pay_days_count'),
            'paid_date' => $request->input('paid_date'),
            'is_black' => toBool($request->input('is_black')),
            'company_branch_id' => $this->companyBranch->id,
            'number' => !$useOneC && $request->filled('invoice_number')
                ? $request->input('invoice_number')
                : "{$this->order->internal_number}-{$customer->internal_number}-" . $this->order->invoices()->count(),
        ]);

        $invoice->customerRequisite()->associate($customer->legal_requisites
            ?: $customer->individual_requisites);
        $invoice->dispatcherLegalRequisite()->associate($contractorRequisite);
        if ($request->filled('date')) {
            $invoice->timestamps = false;
            $invoice->created_at = $request->input('date', now());
        }
        if ($this->order instanceof Order && $this->order->isAvitoOrder()) {

            $invoice->number = $this->order->external_id;
            $count = $this->order->invoices()->where('type', '!=', 'avito_dotation')->count();
            if ($count > 0) {
                $invoice->number .= '/' . $count;
            }
        }
        $this->order->invoices()->save($invoice);
        $isTimeCalculation = $request->input('type') === 'time_calculation';

        $items = $this->splitByMonths(
            $isTimeCalculation
                ? $request->input('items')
                : $request->input('positions'),
            $isTimeCalculation,
            $request->boolean('split_by_month')
        );

        $items = collect($items);

        $depositSum = 0;

        $servicesToSum = OrderComponentService::query()->whereDoesntHave('invoicePosition')
            ->whereHas('orderComponent', function (Builder $q) use (
                $items
            ) {
                $q->whereIn('order_workers.id', $items->pluck('id')->toArray());
            });

        $partsToSum = Item::query()->whereDoesntHave('invoicePosition')
            ->whereHasMorph('owner', [OrderComponent::class], function (Builder $q) use (
                $items
            ) {
                $q->whereIn('order_workers.id', $items->pluck('id')->toArray());
            })->get();


        $isVatSystem =
            $this->companyBranch->getSettings()->price_without_vat && $this->order->contractorRequisite && $this->order->contractorRequisite->vat_system === Price::TYPE_CASHLESS_VAT;


        $servicesToSum = $isTimeCalculation
            ? $servicesToSum->get()
            : $this->getNestedServices($items, $isVatSystem);
        $existsServices = [];

        foreach ($items as $item) {
            /** @var OrderComponent $component */
            $component = $this->order->components()->findOrFail($item['id']);

            $actualComponent =
                $component->actual
                    ?: $component;

            $hasDelivery =
                $this->order->invoices()->whereHas('orderComponents', function (Builder $q) use (
                    $component
                ) {
                    $q->where('order_workers.id', $component->id);
                })->exists();
            $orderType =
                $component->actual
                    ? $component->actual->order_type
                    : $component->order_type;

            //  $isVatSystem = $this->companyBranch->getSettings()->price_without_vat && $component->getContractorRequisite() && $component->getContractorRequisite();
            if ($component->getInvoiceDuration()) {
                $item['order_duration'] = $component->getInvoiceDuration();
                $item['cost_per_unit'] = $actualComponent->cost_per_unit * $component->month_duration;
            } else {
                $item['cost_per_unit'] = (!$isTimeCalculation
                    ?
                    Price::addVat(numberToPenny($item['cost_per_unit']), $isVatSystem
                        ? $this->companyBranch->domain->vat
                        : 0)
                    : $actualComponent->cost_per_unit);
            }

            $itemsToSum = [
                'order_duration' => $item['order_duration'],
                'order_type' => $orderType,
                'cost_per_unit' => $item['cost_per_unit'],
                'delivery_cost' => $hasDelivery && $isTimeCalculation
                    ? 0
                    : (!$isTimeCalculation
                        ?
                        Price::addVat(numberToPenny($item['delivery_cost']), $isVatSystem
                            ? $this->companyBranch->domain->vat
                            : 0)
                        : $actualComponent->delivery_cost),

                'value_added' => $actualComponent->value_added, //($isTimeCalculation
//                    ? $actualComponent->value_added
//                    : 0),

                'date_from' => $item['date_from'],

                'date_to' => $item['date_to'],

                'return_delivery' => $hasDelivery && $isTimeCalculation
                    ? 0
                    : (!$isTimeCalculation
                        ?
                        Price::addVat(numberToPenny($item['return_delivery']), $isVatSystem
                            ? $this->companyBranch->domain->vat
                            : 0)
                        : $actualComponent->return_delivery),
            ];
            $invoice->orderComponents()->attach([
                $item['id'] => $itemsToSum
            ]);

            //Lkz eckeu evyj
            $sum += ($itemsToSum['order_duration'] * ($itemsToSum['cost_per_unit'] + $itemsToSum['value_added']));
            if (!in_array($component->id, $existsServices)) {
                $sum += $itemsToSum['delivery_cost']
                    + $itemsToSum['return_delivery']
                    + $actualComponent->insurance_cost
                    + $servicesToSum->where('order_component_id', $component->id)
                        ->sum(function ($service) use ($isTimeCalculation) {
                            return $service->count * ($isTimeCalculation ? ($service->price + $service->value_added) : ($service->custom_price + $service->value_added));
                        })
                    + $partsToSum->where('owner_id', $component->id)
                        ->sum(function ($item) {
                            return $item->cost_per_unit * $item->amount;
                        });
            }

            $sum -= $component->avito_dotation_sum;

            $existsServices[] = $component->id;

            if ($request->filled('partial_percent') && !$isTimeCalculation) {
                $sum = $request->input('partial_percent') / 100 * $sum;
            }
        }
        $partialPercent =
            $request->input('partial_percent', 100)
                ?: 100;

        $globalDotationSum = 0;
        foreach ($this->order->components as $position){
            $globalDotationSum += $position->avito_dotation_sum;
        }
        $itemsIds = $items->pluck('id')->toArray();
        if ($this->order instanceof Order && $this->order->isAvitoOrder()) {

            $hold = $this->order->avito_order->hold;
            $sum -= $hold;
//            $paidSum = $this->order
//                ->invoices()
//                ->where('type', '!=', 'avito_invoice')
//                ->where('type', '!=', 'avito_dotation')
//                ->whereHas('positions' , function ($q) use ($itemsIds) {
//                    $q->whereIn('owner_id', $itemsIds);
//                })
//                ->where('is_paid', 1)->sum('paid_sum');
//            $sum -= $paidSum;
            $avitoDotationInvoice = $this->order->invoices()->where('type', 'avito_dotation')->first();
            if($avitoDotationInvoice) {
                $diff = $globalDotationSum - $avitoDotationInvoice?->sum ?? 0;

                if ($diff) {
                    $invoiceRepository = new InvoiceRepository($this->order);
                    $avitoInvoice = $invoiceRepository->updateDispatcherInvoice($globalDotationSum, $avitoDotationInvoice);
                    (new OrderDocumentService([], $this->order->company_branch))->formInvoice($avitoInvoice, false, 'default_avito_invoice_url');
                }
            }
        }

        $invoice->update([
            'sum' => $sum,
            'type' => $request->input('type'),
            'partial_percent' => $isTimeCalculation
                ? 100
                : $partialPercent,
        ]);

        $dpSum = 0;
        if ($request->filled('deposits')) {
            $dpSum = $this->getDepositSum($request->input('deposits'), $this->order->customer, $invoice);
        }
        //1c

        $data = [];
        $counter = 0;
        $deliveryCost = 0;
        if ($dpSum > 0) {
            $invoice->load('receivingFromDonor');
            $donorSum = $invoice->receivingFromDonor->sum('sum');
            $totalPositionsCounter = $invoice->orderComponents()->wherePivot('delivery_cost', '>', 0)->count()
                + $invoice->orderComponents()->wherePivot('return_delivery', '>', 0)->count()
                + $invoice->orderComponents()->count()
                + $servicesToSum->count();
            $partial = ($sum - $donorSum) / $totalPositionsCounter;
        }
        $existsComponent = [];
        foreach ($invoice->orderComponents as $component) {
            $costPerUnit = isset($partial)
                ? $partial / $component->pivot->order_duration
                : $component->pivot->cost_per_unit + $component->pivot->value_added;
            $dateFrom = Carbon::parse($component->pivot->date_from);
            $dateTo = Carbon::parse($component->pivot->date_to);

            $data[] = $arr = [
                'is_delivery' => 0,
                'name' => $component->worker instanceof Machinery ? getMachineryValueByMask($this->companyBranch->getSettings()->machinery_document_mask, $component->worker,
                    [
                        'address' => $this->order->address,
                        'attributes' => $component->order?->lead->positions->firstWhere('type_id', $component->worker->type)?->category_options?->join(' '),
                        'description' => $component->description,
                        'externalId' => $this->order->external_id,
                        'createdAt' => $this->order->created_at?->format('d.m.Y'),
                        'dateFrom' => $dateFrom->format('d.m.Y'),
                        'dateTo' => $dateTo->format('d.m.Y'),
                        'timeFrom' => $dateFrom->format('H:i'),
                        'timeTo' => $dateTo->format('H:i'),
                    ]) : $component->worker->name,
                'description' => ($component->description ? "{$component->description} " : '') . "({$dateFrom->format('d.m.Y' . ($component->worker?->shift_duration === 24 ? ' H:i' : ''))} - {$dateTo->format('d.m.Y' . ($component->worker?->shift_duration === 24 ? ' H:i' : ''))}) "
                    . (!$isTimeCalculation && $partialPercent && $partialPercent < 100
                        ? trans('transbaza_finance.prepayment_percent', ['percent' => $partialPercent])
                        : ''),
                'vendor_code' => $component->worker->board_number,
                'sum' => $costPerUnit / 100,
                'amount' => $component->pivot->order_duration,
                'vat' => $this->order->contractorRequisite->vat_system === Price::TYPE_CASHLESS_VAT
                    ? 20
                    : 0,
            ];

            if (!$component->worker instanceof WarehousePartSet) {
                $position = new InvoiceItem([
                    'vendor_code' => $arr['vendor_code'],
                    'cost_per_unit' => $costPerUnit + $component->insurance_cost_per_unit,
                    'amount' => $arr['amount'],
                    'name' => $arr['name'],
                    'description' => $arr['description'],
                    'unit' => $component->order_type === 'shift'
                        ?
                        $this->companyBranch->getSettings()->getActualShiftName($component->worker->change_hour, $component->is_month)
                        : trans('units.h')
                ]);
                $position->owner()->associate($component);
                $invoice->positions()->save($position);
            }

            if (in_array($component->id, $existsComponent))
                continue;

            foreach ($servicesToSum->where('order_component_id', $component->id) as $service) {

                $serviceCustomCost =
                    !$isTimeCalculation
                        ? ($service->custom_price + $service->value_added)
                        : ($service->price + $service->value_added);
                $data[] = $arr = [
                    'is_delivery' => 0,
                    'name' => $service->name,
                    'description' => $service->name,
                    'vendor_code' => $service->customService->vendor_code ?? null,
                    'sum' => (($partial ?? $serviceCustomCost) / 100),
                    'amount' => $service->count,
                    'vat' => $this->order->contractorRequisite->vat_system === Price::TYPE_CASHLESS_VAT
                        ? 20
                        : 0,
                ];
                // logger( $service->customService);
                $position = new InvoiceItem([
                    'vendor_code' => $arr['vendor_code'],
                    'cost_per_unit' => $partial ?? $serviceCustomCost,
                    'amount' => $arr['amount'],
                    'unit' => $service->customService->unit->name ?? '',
                    'name' => $service->name,
                    'description' => $service->name,
                ]);
                $position->owner()->associate($service);
                $invoice->positions()->save($position);
            }

            foreach ($partsToSum->where('owner_id', $component->id) as $part) {

                $data[] = $arr = [
                    'is_delivery' => 0,
                    'name' => $part->part->name,
                    'description' => $part->part->name,
                    'vendor_code' => $service->customService->vendor_code ?? null,
                    'sum' => ($part->cost_per_unit / 100),
                    'amount' => $part->amount,
                    'vat' => $this->order->contractorRequisite->vat_system === Price::TYPE_CASHLESS_VAT
                        ? 20
                        : 0,
                ];
                // logger( $service->customService);
                $position = new InvoiceItem([
                    'vendor_code' => $part->part->vendor_code,
                    'cost_per_unit' => $part->cost_per_unit,
                    'amount' => $part->amount,
                    'unit' => $part->unit->name,
                    'name' => $part->part->name,
                    'description' => $part->part->name,
                ]);
                $position->owner()->associate($part);
                $invoice->positions()->save($position);
            }

            foreach ($component->rent_parts->where('type', 'rent') as $rent_part) {

                $part = $rent_part->company_branches_warehouse_part->part;

                $costPerUnit = $rent_part->cost_per_unit;

                $data[] = $arr = [
                    'is_delivery' => 0,
                    'name' => $rent_part->company_branches_warehouse_part->name ?: $part->name,
                    'description' => $rent_part->company_branches_warehouse_part->name ?: $part->name,
                    'vendor_code' => $rent_part->company_branches_warehouse_part->vendor_code ?: $part->vendor_code,
                    'sum' => $costPerUnit,
                    'amount' => $rent_part->count,
                    'vat' => $this->order->contractorRequisite->vat_system === Price::TYPE_CASHLESS_VAT
                        ? 20
                        : 0,
                ];

                $position = new InvoiceItem([
                    'vendor_code' => $part->vendor_code,
                    'cost_per_unit' => $costPerUnit,
                    'amount' => $rent_part->count,
                    'unit' => $part->unit->name ?? '',
                    'name' => $part->name,
                    'part_duration' => $component->pivot->order_duration,
                    'description' => $part->name,
                ]);
                $position->owner()->associate($part);
                $invoice->positions()->save($position);
            }


            if ($component->pivot->delivery_cost || $component->pivot->return_delivery) {

                if ($component->pivot->delivery_cost) {
                    ++$counter;
                    $deliveryCost += $partial ?? $component->pivot->delivery_cost;
                }
                if ($component->pivot->return_delivery) {
                    ++$counter;
                    $deliveryCost += $partial ?? $component->pivot->return_delivery;
                }
            }
            $existsComponent[] = $component->id;
        }
        if ($counter > 0) {
            $data[] = $arr = [
                'is_delivery' => $counter,
                'vendor_code' => 'delivery',
                'name' => 'Доставка',
                'description' => 'Доставка',
                'sum' => $deliveryCost / 100,
                'amount' => 1,
                'vat' => $this->order->contractorRequisite->vat_system === Price::TYPE_CASHLESS_VAT
                    ? 20
                    : 0,
            ];
            $position = new InvoiceItem([
                'vendor_code' => $arr['vendor_code'],
                'cost_per_unit' => $deliveryCost / $counter,
                'amount' => $counter,
                'unit' => trans('units.count_short'),
                'name' => trans('transbaza_proposal_search.delivery'),
                'description' => trans('transbaza_proposal_search.delivery'),
            ]);
            $invoice->positions()->save($position);
        }
        if ($this->companyBranch->OneCConnection && $useOneC) {
            $connection = new OneCService($this->companyBranch);

            $response =
                $connection->addInvoice($invoice->number, $invoice->id, $customer->getRequisites(), $this->order->contractorRequisite->inn, $data);


            if ($response['code'] !== 200) {
                return response()->json([
                    'errors' => $response['message']
                ], 400);
            }

            if (!empty($response['message']['Number'])) {
                $invoice->update([
                    'onec' => 1,
                    'number' => $response['message']['Number']
                ]);
            }

        }
        if ($this->order instanceof Order && $this->order->isAvitoOrder()) {
            (new OrderDocumentService([], $this->order->company_branch))->formInvoice($invoice, false, 'default_avito_invoice_url');
        }
        \DB::commit();

    }

    function syncOneC($id)
    {
        $invoice = DispatcherInvoice::query()->forBranch()->where('onec', false)->findOrFail($id);

        $lock = \Cache::lock("invoice_generate_{$invoice->id}", 10);

        if ($this->companyBranch->OneCConnection && $lock->get()) {
            $connection = new OneCService($this->companyBranch);
            $data = [];

            foreach ($invoice->positions as $position) {

                /*if($position->name === trans('transbaza_proposal_search.delivery')) {
                    $data[] = [
                        'is_delivery' => 1,
                        'name' => $position->name,
                        'vendor_code' => 'delivery',
                        'sum' => ($position->cost_per_unit * $position->amount / 100),
                        'amount' => 1,
                        'vat' => $invoice->owner->contractorRequisite->vat_system === Price::TYPE_CASHLESS_VAT ? 20 : 0,
                    ];
                }else  {*/
                $data[] = [
                    'is_delivery' => $position->vendor_code === 'delivery' ? $position->amount : 0,
                    'description' => $position->description,
                    'vendor_code' => $position->vendor_code,
                    'sum' => $position->cost_per_unit / 100,
                    'amount' => $position->amount,
                    'name' => $position->name,
                    'vat' => $invoice->owner->contractorRequisite->vat_system === Price::TYPE_CASHLESS_VAT
                        ? 20
                        : 0,
                ];
                /*}*/
            }
            //$req = $invoice->customerRequisite->toArray();
            //$req = array_merge($req, ['contract' => $invoice->customerRequisite->owner?->generateContract($invoice->owner->contractorRequisite)->toArray()]);
            $requisites = $invoice->customerRequisite->toArray();
            $bankRequisites = $invoice->owner->bankRequisite;
            if ($bankRequisites) {
                $requisites['bank_requisites'] = $bankRequisites;
            }
            if ($invoice->owner->contract) {
                $requisites['contract'] = $invoice->owner->contract->toArray();
            }
            $response =
                $connection->addInvoice($invoice->number, $invoice->id, $requisites, $invoice->owner->contractorRequisite->inn, $data);


            if ($response['code'] !== 200) {
                $lock->release();

                return response()->json([
                    'errors' => $response['message']
                ], 400);
            }

            if (!empty($response['message']['Number'])) {
                $invoice->update([
                    'onec' => 1,
                    'number' => $response['message']['Number']
                ]);
            }

        }
    }

    private function getDepositSum(
        $deposits,
        Customer $customer,
        DispatcherInvoice $invoice)
    {

        $dpSum = 0;
        foreach ($deposits as $deposit) {
            $currentSum = numberToPenny($deposit['sum']);
            $depositInvoice =
                DispatcherInvoice::query()->whereHasMorph('customerRequisite', [EntityRequisite::class, IndividualRequisite::class, InternationalLegalDetails::class], function (
                    $q) use (
                    $customer
                ) {
                    $q->where('requisite_id', $customer->id);
                })
                    ->withSum(
                        'donorTransfers as transferSum', 'sum',
                    )->havingRaw('`paid_sum` > transferSum')
                    ->findOrFail($deposit['id']);
            if ($currentSum <= $depositInvoice->paid_sum - $depositInvoice->transferSum) {

                DispatcherInvoiceDepositTransfer::create([
                    'donor_invoice_id' => $depositInvoice->id,
                    'current_invoice_id' => $invoice->id,
                    'sum' => $currentSum,
                ]);
                $dpSum += $currentSum;
            } else {
                throw ValidationException::withMessages(['errors' => 'Некорректные данные по депозиту']);
            }
        }


        return $dpSum;
    }

    private function splitByMonths(
        $items,
        $timeCalculation,
        $splitOneMonth = false,
    )
    {
        $result = [];
        /** @var CompanyBranchSettings $settings */
        $splitByMonth = $this->companyBranch->getSettings()->split_invoice_by_month;
        $monthDuration = TariffUnitCompare::query()->forBranch()->where('is_month', true)->first()?->amount ?? 30;
        $mapping = [];
        foreach ($items as $item) {
            $component = $this->order->components()->findOrFail($item['id']);
            $duration = $item['order_duration'];
            if ($splitOneMonth && !$component->is_month && $component->order_type === TimeCalculation::TIME_TYPE_SHIFT) {
                $mappingKey = implode('-', [$component->worker_type, $component->worker_id]);
                if (!isset($mapping[$mappingKey])) {
                    $mapping[$mappingKey] = $monthDuration;
                }
                $item['order_duration'] = $mapping[$mappingKey] > $duration
                    ? (float)$duration
                    : $mapping[$mappingKey];
                $mapping[$mappingKey] -= $duration;

                if ($item['order_duration'] <= 0) {
                    continue;
                }
                $item['date_from'] = $component->date_from;
                $item['date_to'] = getDateTo($component->date_from, TimeCalculation::TIME_TYPE_SHIFT, $item['order_duration']);
                $result[] = $item;

                continue;
            }
            defaultCalculation:
            if ($component->worker instanceof WarehousePartSet || $component->is_month) {
                //$item['order_duration'] = $item['order_duration'];// $dateFrom->copy()->endOfMonth()->diffInDays($dateFrom) + 1;
                $item['date_from'] = $component->date_from;
                $item['date_to'] = $component->date_to;
                $result[] = $item;
                continue;
            }
            $orderType =
                $component->actual
                    ? $component->actual->order_type
                    : $component->order_type;

            // $item['order_duration'] = ($component->actual ? $component->actual->order_duration : $component->order_duration);
            // $item['cost_per_unit'] = ($component->actual ? $component->actual->order_duration : $component->order_duration);

            if ($orderType === TimeCalculation::TIME_TYPE_HOUR) {
                $item['date_from'] = $timeCalculation
                    ? ($component->actual
                        ? $component->actual->date_from->copy()
                        : $component->date_from->copy())
                    : $item['date_from'];
                $item['date_to'] = $timeCalculation
                    ? ($component->actual
                        ? $component->actual->date_to->copy()
                        : $component->date_to->copy())
                    : getDateTo($item['date_from'], $orderType, $item['order_duration']);

                $result[] = $item;
                continue;
            }
            $existsPayment =
                $this->order->invoices()->whereHas('orderComponents', function (Builder $q) use (
                    $component
                ) {
                    $q->where('order_workers.id', $component->id);
                })->get();


            if ($timeCalculation) {
                $existsPayment = $existsPayment->map(function ($payment) {

                    $data = collect();
                    foreach ($payment->orderComponents as $position) {
                        if ($position->pivot->date_to) {
                            $data->push([
                                'date_to' => strtotime((string)$position->pivot->date_to)
                            ]);
                        }
                    }
                    $payment->date_to = $data->max('date_to');
                    return $payment;
                });

                $dateFrom =
                    $existsPayment->max('date_to')
                        ? Carbon::createFromTimestamp($existsPayment->max('date_to'))
                        : ($component->actual
                        ? $component->actual->date_from->copy()
                        : $component->date_from->copy());
            } else {
                $dateFrom = Carbon::parse($item['date_from'])->format('Y-m-d');
                $dateFrom = Carbon::parse($dateFrom . ' ' . $component->date_from->format('H:i'));
            }

            if ($component->worker instanceof WarehousePartSet) {
                $dateTo = $component->actual
                    ? $component->actual->date_to->copy()
                    : $component->date_to->copy();
            } else {
                $dates = $component->worker->getDatesForOrder($dateFrom->copy(),
                    $item['order_duration'], $orderType, null, [$component->id], $component->worker->change_hour);
                $dateTo = Carbon::parse(last($dates));
                if ($component->worker->change_hour === 24) {
                    $diff = $dateFrom->copy()->startOfDay()->diffInMinutes($dateFrom);
                    $dateTo->startOfDay()->addMinutes($diff)->subMinute();
                }
            }
            $ids = [];
            if ($dateFrom->format('m') !== $dateTo->format('m') && $splitByMonth) {

                while ($dateFrom->format('m') !== $dateTo->format('m')) {
                    $dt = $dateFrom->copy()->endOfMonth();
                    $currentIds = $component->calendar()
                        ->forPeriod($dateFrom->copy(), $dt->copy())
                        ->whereNotIn('id', $ids)
                        ->get()
                        ->unique(function ($item) use (
                            $orderType
                        ) {
                            return $item->startDate->format($orderType === TimeCalculation::TIME_TYPE_HOUR
                                ? 'Y-m-d H:i'
                                : 'Y-m-d');
                        })
                        ->pluck('id')
                        ->toArray();
                    $ids = array_merge($ids, $currentIds);
                    $item['order_duration'] =
                        count($currentIds);// $dateFrom->copy()->endOfMonth()->diffInDays($dateFrom) + 1;
                    $item['date_from'] = $dateFrom->copy();
                    $item['date_to'] = $dt->greaterThan($dateTo) ? $dateTo->copy() : $dt->copy();


                    if ($component->worker->change_hour === 24 && $dateFrom->format('m') === $dateTo->format('m')) {
                        $item['order_duration'] -= 1;
                        if ($dateTo->day === 1) {
                            $item['date_to'] = $dateTo->copy();
                        }
                    }
                    $dateFrom->addMonthNoOverflow()->startOfMonth();
                    $result[] = $item;
                }
                $item['order_duration'] =
                    $component->calendar()->forPeriod($dateFrom->copy(), $dateTo->copy())->whereNotIn('id', $ids)
                        ->get()
                        ->unique(function ($item) use (
                            $orderType
                        ) {
                            return $item->startDate->format($orderType === TimeCalculation::TIME_TYPE_HOUR
                                ? 'Y-m-d H:i'
                                : 'Y-m-d');
                        })->count();
                if ($component->worker->change_hour === 24) {
                    $item['order_duration'] -= 1;
                }

                if ($item['order_duration'] > 0) {
                    $item['date_from'] = $dateFrom->copy();
                    $item['date_to'] = $dateTo->copy();
                    $result[] = $item;
                }


            } else {
                //  $item['order_duration'] = $component->calendar()->forPeriod($dateFrom->copy(), $dateTo->copy())->count();
                $item['date_from'] = $dateFrom->copy();
                $item['date_to'] = $dateTo->copy();
                $result[] = $item;
            }
        }
        return $result;
    }

    /**
     * Show the specified resource.
     * @param int $id
     * @return Response
     */
    public function show($id)
    {
        return $this->order->invoices()->findOrFail($id);
    }


    /**
     * Remove the specified resource from storage.
     * @param int $id
     * @return JsonResponse
     */
    public function destroy(int $id): JsonResponse
    {
        $invoice = $this->order->invoices()->findOrFail($id);
        if ($this->order instanceof Order) {
            OrderRepository::revertToPrepare($this->order);
        }
        $invoice->documents()->delete();
        $invoice->delete();
        if ($this->order->invoices()->where('type', '!=', 'avito_dotation')->count() === 0) {
            $avitoDotationInvoice = $this->order->invoices()->where('type', 'avito_dotation')->first();
            if ($avitoDotationInvoice) {
                $avitoDotationInvoice->documents()->delete();
                $avitoDotationInvoice->delete();
            }
        }
        return response()->json();
    }

    function releaseServices($id)
    {
        $invoice = DispatcherInvoice::query()->forBranch()->findOrFail($id);

        if ($invoice->one_c_info) {
            $connection = new OneCService($this->companyBranch);

            $response = $connection->addRelease($invoice->one_c_info['Ref_Key'], $invoice->positions
                ->map(function ($item) use ($invoice) {
                    $item->vat = $invoice->owner->contractorRequisite->vat_system === Price::TYPE_CASHLESS_VAT
                        ? 20
                        : 0;
                    $item->type = in_array($item->owner_type, [OrderComponentService::class, CustomService::class]) ? 'service' : 'entity';
                    return $item;
                }));

            if ($response['code'] !== 200) {

                return response()->json([
                    'errors' => $response['message']
                ], 400);
            }
            $invoice->update(
                ['onec_release_info' => $response['message']]
            );
            return response()->json();
        }

        return response()->json([], 400);
    }

    public function updatePaidDate(Request $request, $id)
    {
        $invoice = DispatcherInvoice::query()->forBranch()->findOrFail($id);
        $invoice->update([
            'paid_date' => $request->input('date')
        ]);
    }
}
