<?php

namespace Modules\ContractorOffice\Services;

use App\Machinery;
use Carbon\Carbon;
use Illuminate\Validation\ValidationException;
use Modules\ContractorOffice\Entities\CompanyWorker;
use Modules\ContractorOffice\Entities\Vehicle\Price;
use Modules\ContractorOffice\Services\Tariffs\TimeCalculation;
use Modules\Orders\Entities\MachineryStamp;
use Modules\Orders\Entities\OrderComponentService;
use Modules\PartsWarehouse\Entities\Stock\Item;
use Modules\PartsWarehouse\Entities\Stock\ItemSerial;
use Modules\PartsWarehouse\Entities\Stock\Stock;
use Modules\PartsWarehouse\Entities\Warehouse\CompanyBranchWarehousePart;
use Modules\PartsWarehouse\Entities\Warehouse\WarehousePartSet;
use Modules\PartsWarehouse\Entities\Warehouse\WarehousePartsOperation;

class OrderService
{
    private $order;
    public $oldChangeHours;
    private $companyBranch;
    private $request;

    public function __construct($order, $oldChangeHours ,$request, $companyBranch)
    {
        $this->order = $order;
        $this->oldChangeHours = $oldChangeHours;
        $this->request = $request;
        $this->companyBranch = $companyBranch;
    }

    public function addPositionItem($item, $orderParams, $type){
        $contract = $this->order->contract ?? $this->order->customer->contracts
            ->where('requisite_id', $this->order->contractor_requisite_id)
            ->where('requisite_type', $this->order->contractor_requisite_type)->first();
        if (!$contract) {
            $contract = $this->order->customer->generateContract($this->order->contractorRequisite);
        }


        $vehicle = null;
        $date = Carbon::parse($orderParams['start_date'])->format('Y-m-d');
        $dateFrom = Carbon::parse("{$date} {$orderParams['start_time']}");

        if (!$type || $type !== 'warehouse_set') {
            $vehicle = $this->order->company_branch->machines();
            //  ->whereInCircle($this->coords['lat'], $this->coords['lng']);

            if ($item['order_type'] === TimeCalculation::TIME_TYPE_HOUR) {

                $vehicle->checkAvailable($dateFrom,
                    getDateTo($dateFrom, $orderParams['order_type'], $orderParams['order_duration']),
                    $orderParams['order_type'], $orderParams['order_duration']);
            }
            //->checkAvailable($position->date_from, $date_to, $position->order_type, $position->order_duration)

            $vehicle = $vehicle->find($item['id']);
            if (!$vehicle) {
                \DB::rollBack();
                throw ValidationException::withMessages([
                    'errors' => ["Техника занята в этот период"]
                ]);
            }
            $costPerUnit = $item['cost_per_unit'];
            $deliveryCost = ($item['delivery_cost'] ?? 0);
            $returnDeliveryCost = ($item['return_delivery'] ?? 0);

            if ($vehicle instanceof Machinery && !empty($item['shift_duration']) && in_array($item['shift_duration'], [8,23, 24])) {
                $this->oldChangeHours[$vehicle->id] = $vehicle->change_hour;
                $vehicle->update(['change_hour' => $item['shift_duration']]);
            }

            $component = $this->order->attachCustomVehicles($vehicle->id,
                numberToPenny($costPerUnit),
                $dateFrom->copy(),
                $orderParams['order_type'],
                $orderParams['order_duration'],
                numberToPenny($deliveryCost),
                numberToPenny($returnDeliveryCost),
                null,
                null,
                /**  Последнее айди приложения к договору для текущего клиента.*/
                $this->order->customer->getAndIncrementApplicationId($contract),
                ($orderParams['comment'] ?? '')
            );
            $cashlessType = $item['cashless_type'] ?? false;
            if ($cashlessType) {
                $component->cashless_type = $cashlessType;
                $component->save();
            }
            $isMonth = toBool($item['is_month'] ?? null);
            if ($isMonth) {
                $component->is_month = true;
                $component->month_duration = $item['month_duration'];
                $component->save();
            }
            //Добавленая стоимость для подрядчика
            if ($vehicle->subOwner) {
                $component->value_added = numberToPenny($item['value_added']);
                $component->save();
            }


            if ($item['order_type'] === 'warm') {
                $driver =
                    CompanyWorker::query()->whereType(CompanyWorker::TYPE_DRIVER)->findOrFail($item['company_worker_id']);
                $component->driver()->associate($driver);
                $component->save();
            };
            $isVatSystem =
                $this->order->company_branch->getSettings()->price_without_vat && $this->order->contractorRequisite && $this->order->contractorRequisite->vat_system === Price::TYPE_CASHLESS_VAT;

            $schedule = $vehicle->getScheduleForDay($dateFrom->format('D'));

            MachineryStamp::createTimestamp($vehicle->id, $this->order->id, 'on_the_way',
                $dateFrom, $this->order->coordinates);

            if (!empty($item['services'])) {
                foreach ($item['services'] as $service) {
                    if (toBool(!$service['active'])) {
                        continue;
                    }
                    $component->services()->save(new OrderComponentService([
                        'price' => Price::addVat(numberToPenny($service['price']), $isVatSystem
                            ? $this->order->company_branch->domain->vat
                            : 0),
                        'name' => $service['name'],
                        'custom_service_id' => $service['id'],
                    ]));
                }
            }
            if (!empty($item['parts'])) {

                foreach ($item['parts'] as $part) {

                    $stock = Stock::query()->forBranch()->findOrFail($part['stock_id']);
                    $serialAccounting = toBool($part['serial'] ?? false);

                    /** @var Item $item */
                    $item = new Item([
                        'part_id' => $part['part_id'],
                        'stock_id' => $stock->id,
                        'unit_id' => $part['unit_id'],
                        'cost_per_unit' => numberToPenny($part['cost_per_unit']),
                        'amount' => $serialAccounting
                            ? 0
                            : $part['amount'],
                        'serial_accounting' => $serialAccounting,
                        'company_branch_id' => $this->order->company_branch->id,
                    ]);
                    $item->owner()->associate($component);
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
            }
        } elseif ($type && $type === 'warehouse_set') {
            $item = $this->request->all();
            $vehicle = WarehousePartSet::query()->create(
                [
                    'type_id' => 268,
                    'company_branch_id' => $this->companyBranch->id,
                    'machinery_base_id' => $this->request->input('machinery_base_id')
                ]
            );
            $parts = collect($item['parts']);
            $parts = $parts->map(function ($p) {
                $p['sum'] = $p['cost_per_unit'] * $p['amount'];
                return $p;
            });

            $costPerUnit = $parts->sum('sum');
            $deliveryCost = ($item['delivery_cost'] ?? 0);
            $returnDeliveryCost = ($item['return_delivery'] ?? 0);

            $component = $this->order->attachCustomVehicles($vehicle->id,
                numberToPenny($costPerUnit),
                $dateFrom,
                $orderParams['order_type'],
                $orderParams['order_duration'],
                numberToPenny($deliveryCost),
                numberToPenny($returnDeliveryCost),
                null,
                null,
                /**  Последнее айди приложения к договору для текущего клиента.*/
                $this->order->customer->getAndIncrementApplicationId($contract),
                ($orderParams['comment'] ?? ''),
                'warehouse_set'
            );
            $cashlessType = $item['cashless_type'] ?? false;
            if ($cashlessType) {
                $component->cashless_type = $cashlessType;
                $component->save();
            }


            if ($item['order_type'] === 'warm') {
                $driver =
                    CompanyWorker::query()->whereType(CompanyWorker::TYPE_DRIVER)->findOrFail($item['company_worker_id']);
                $component->driver()->associate($driver);
                $component->save();
            };
            $isVatSystem =
                $this->order->company_branch->getSettings()->price_without_vat && $this->order->contractorRequisite && $this->order->contractorRequisite->vat_system === Price::TYPE_CASHLESS_VAT;


            MachineryStamp::createTimestamp($vehicle->id, $this->order->id, 'on_the_way',
                "{$dateFrom->format('Y-m-d H:i:s')}", $this->order->coordinates);

            if (!empty($item['parts'])) {

                foreach ($item['parts'] as $part) {

                    $cbwp = CompanyBranchWarehousePart::query()->where([
                        'part_id' => $part['part_id'],
                        'company_branch_id' => $this->order->company_branch->id,
                    ])->firstOrFail();

                    $vehicle->parts()->attach($cbwp->id, ['count' => $part['amount']]);
                    WarehousePartsOperation::query()->create([
                        'company_branches_warehouse_part_id' => $cbwp->id,
                        'order_worker_id' => $component->id,
                        'type' => 'rent',
                        'count' => $part['amount'],
                        'cost_per_unit' => numberToPenny($part['cost_per_unit']),
                        'begin_date' => $component->date_from,
                        'end_date' => $component->date_to,
                    ]);

                }
            }
        }

        $this->order->types()->attach($vehicle->type, ['brand_id' => 0, 'comment' => ('')]);
    }

}
