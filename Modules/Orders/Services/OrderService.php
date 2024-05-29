<?php

namespace Modules\Orders\Services;

use App\Finance\TinkoffMerchantAPI;
use App\Helpers\RequestHelper;
use App\Machinery;
use App\Machines\FreeDay;
use App\Service\Insurance\InsuranceService;
use App\User;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Modules\CompanyOffice\Entities\Company\CompanyBranch;
use Modules\CompanyOffice\Services\CompaniesService;
use Modules\ContractorOffice\Entities\Vehicle\MachineryBase;
use Modules\ContractorOffice\Entities\Vehicle\Price;
use Modules\ContractorOffice\Services\Tariffs\TimeCalculation;
use Modules\Dispatcher\Entities\Customer;
use Modules\Dispatcher\Entities\Lead;
use Modules\Integrations\Services\Telegram\TelegramService;
use Modules\Orders\Entities\MachineryStamp;
use Modules\Orders\Entities\Order;
use Modules\Orders\Entities\OrderComponent;
use Modules\Orders\Entities\OrderComponentActual;
use Modules\Orders\Entities\OrderComponentHistory;
use Modules\Orders\Entities\OrderComponentIdle;
use Modules\Orders\Entities\OrderComponentReportTimestamp;
use Modules\Orders\Entities\OrderComponentService;
use Modules\Orders\Entities\OrderComponentServiceActual;
use Modules\Orders\Entities\OrderManagement;
use Modules\Orders\Entities\Payment;
use Modules\Orders\Jobs\SendOrderInvoice;
use Modules\PartsWarehouse\Entities\Posting;
use Modules\PartsWarehouse\Entities\Stock\Item;
use Modules\PartsWarehouse\Entities\Warehouse\WarehousePartSet;
use Modules\PartsWarehouse\Entities\Warehouse\WarehousePartsOperation;
use Modules\Profiles\Entities\UserNotification;
use TypeError;

class OrderService
{

    /** @var $var Order */
    private $order;

    public $isVatSystem;

    private ?TelegramService $telegramService = null;

    function setOrder(Order $order)
    {
        $this->order = $order;

        $this->telegramService = new TelegramService();

        $this->isVatSystem =
            $this->order->company_branch->getSettings()->price_without_vat && $this->order->contractorRequisite && $this->order->contractorRequisite->vat_system === Price::TYPE_CASHLESS_VAT;

        return $this;
    }


    function donePosition(
        OrderComponent $component,
        $base_id = null,
        $actual = false
    ) {
        if ($actual && $component->actual) {
            $component->update([
                'amount' => $component->actual->amount,
                'cost_per_unit' => $component->actual->cost_per_unit,
                'date_from' => $component->actual->date_from,
                'date_to' => $component->actual->date_to,
                'delivery_cost' => $component->actual->delivery_cost,
                'status' => OrderComponent::STATUS_DONE,
                'order_type' => $component->actual->order_type,
                'order_duration' => $component->actual->order_duration,
                'return_delivery' => $component->actual->return_delivery,
                'value_added' => $component->actual->value_added

            ]);
            $component->services()->delete();
            foreach ($component->actual->services as $service) {
                $component->services()->save(new OrderComponentService([
                    'price' => $service->price,
                    'name' => $service->name,
                    'count' => $service->count,
                    'value_added' => $service->value_added,
                    'custom_service_id' => $service->custom_service_id,
                ]));
            }
        } else {
            if ($component->worker instanceof Machinery) {
                $component->actual?->delete();
                $this->restoreCalendar($component);
            }

        }
        $component->update([
            'complete' => 1,
            'finish_date' => now(),
            'status' => Order::STATUS_DONE
        ]);
        $timezone = $component->order->company_branch->timezone;
        if (!$actual && $component->worker instanceof Machinery) {

            $calendar = $component->calendar()->where('order_component_id', $component->id)
                ->forPeriod(now($timezone), now($timezone)->addDay())
                ->orderBy('endDate')
                ->first();

            if ($calendar) {
                if ($calendar->endDate->gt(now($timezone)) && $calendar->startDate->lt(now($timezone))) {
                    $calendar->update([
                        'endDate' => now($timezone)->addMinute()
                    ]);
                }
                $component->calendar()->where('endDate', '>', now($timezone)->addMinutes(2))
                    ->where('id', '!=', $calendar->id)
                    ->delete();
            }
            $component->worker->update([
                'engine_hours_after_tw' => $component->order_duration * (TimeCalculation::TIME_TYPE_HOUR === $component->order_type ? 1 : $component->worker->shift_duration),
                'days_after_tw' => $component->calendar->unique(fn(FreeDay $day) => $day->startDate->format('d-m-Y'))->count(),
            ]);
        }


        if ($base_id) {
            $base = MachineryBase::query()->forBranch()->findOrFail($base_id);
            if ($component->machinery_type === Machinery::class) {
                $component->worker->update([
                    'base_id' => $base->id
                ]);
            } else {
                $component->worker->update([
                    'machinery_base_id' => $base->id
                ]);
            }
        }
        MachineryStamp::query()
            ->where('order_id', $component->order_id)
            ->where('machinery_id', $component->worker_id)
            ->where('machinery_type', $component->worker_type)
            ->where('type', 'done')
            ->delete();
        MachineryStamp::createTimestamp($component->worker_id, $component->order_id, 'done', now($timezone), null,
            $component->worker_type);


        if ($component->order->components()->where('complete',
                1)->count() === $component->order->components()->count()) {
            $component->order->done();
        }

        /*        foreach ($component->parts as $item) {
                    $item->update([
                        'amount' => 0
                    ]);
                }*/
        if ($component->worker_type === WarehousePartSet::class) {
            foreach ($component->rent_parts as $item) {
                $newItem = $item->replicate();
                $newItem->type = 'return';
                $newItem->push();
            }
        }
        return $this;
    }

    function restoreCalendar(OrderComponent $application): void
    {
        $i = 0;
        $orderType = $application->order_type;
        $application->calendar()->delete();
        $dates = $application->worker->getDatesForOrder($application->date_from->copy(), $application->order_duration,
            $orderType);
        $dateTo = getDateTo($application->date_from->copy(), $orderType, $application->order_duration);

        if ($orderType === TimeCalculation::TIME_TYPE_SHIFT) {
            if ($application->worker->change_hour === 24) {
                $diffMinutes = $application->date_from->copy()->startOfDay()->diffInMinutes($application->date_from);
                $dateTo->startOfDay()->addMinutes($diffMinutes)->subMinute();
            } else {

                $lastDay = $application->worker->getScheduleForDay($dateTo->format('D'));
                $dateTo->startOfDay()
                    ->addHours($lastDay->time_to[0])
                    ->addMinutes($lastDay->time_to[1]);

                //$dateTo->endOfDay();
            }
        }
        foreach ($dates as $date) {
            ++$i;
            $startDate = Carbon::parse($date);
            $endDate = $startDate->copy();

            $currentDay = $application->worker->getScheduleForDay($startDate->format('D'));

            if ($i !== 1) {
                $startDate->startOfDay()
                    ->addHours($currentDay->time_from[0])
                    ->addMinutes($currentDay->time_from[1]);
            }
            if ($orderType === TimeCalculation::TIME_TYPE_HOUR) {
                $endDate = $dateTo;
            } else {
                $endDate->setHour($currentDay->time_to[0]);
                $endDate->setMinute($currentDay->time_to[1]);
            }
            if ($startDate->gt($endDate)) {
                $endDate->endOfDay();
            }

            if ($i === count($dates)) {
                $endDate = $dateTo;
            }
            FreeDay::create([
                'startDate' => $startDate,
                'endDate' => $endDate,
                'type' => 'order',
                'order_id' => $application->order_id,
                'order_component_id' => $application->id,
                'machine_id' => $application->worker->id
            ]);
        }

    }

    function returnToWork(OrderComponent $component)
    {
        $component->update(['complete' => 0, 'finish_date' => null, 'status' => Order::STATUS_ACCEPT, 'reject_type' => null]);
        MachineryStamp::query()->where('machinery_id', $component->worker_id)->where('machinery_type',
            get_class($component->worker))
            ->where('order_id', $component->order_id)->whereNotIn('type', ['on_the_way', 'arrival'])->delete();
        MachineryStamp::query()->where('machinery_id', $component->worker_id)->where('machinery_type',
            get_class($component->worker))
            ->where('order_id', $component->order_id)->update([
                'created_at' => now()
            ]);
        $component->order->returnToWork();

        if ($component->worker_type === WarehousePartSet::class) {
            foreach ($component->rent_parts->whereIn('type', ['reject', 'return']) as $item) {
                $item->delete();
            }
        }

        return $this;
    }

    function setIdle(
        $orderComponentId,
        $dateFrom,
        $dateTo,
        $type = 1
    ) {
        /** @var OrderComponent $component */
        $component = $this->order->components()->findOrFail($orderComponentId);

        $component->idle_periods()->save(new OrderComponentIdle([
            'date_from' => $dateFrom,
            'date_to' => $dateTo,
            'type' => $type
        ]));

        return $this;
    }

    /**
     * Пролонгация заказа на определенную технику
     * @param $orderComponentId
     * @param $duration
     * @param $cost_per_unit
     * @param  bool  $clone
     * @param  null  $order_type
     * @return $this
     * @throws ValidationException
     */
    function prolongation(
        $orderComponentId,
        $duration,
        $cost_per_unit,
        $clone = false,
        $order_type = null,
        $valueAdded = 0,
        $shift_duration = null,
        $startDate = null,
        $startTime = null
    ) {
        /** @var OrderComponent $component */
        $component = $this->order->components()->findOrFail($orderComponentId);
        $calculatedDateTo = $component->date_to;
        if(!empty($startDate) && !empty($startTime)){
            $time = Carbon::parse($startTime)->format('H:i');
            $calculatedDateTo = Carbon::parse($startDate .' '. $time);
        }
        if ($component->worker_type == WarehousePartSet::class) {

            if ($component->order_type == TimeCalculation::TIME_TYPE_HOUR) {
                $dateTo = $calculatedDateTo->addHours($duration);
            } elseif ($component->order_type == TimeCalculation::TIME_TYPE_SHIFT) {
                $dateTo = $calculatedDateTo->addDays($duration);
            }

            $component->increment('order_duration', $duration);
            $component->update([
                'date_to' => $dateTo->format('Y-m-d H:i:s'),
                'amount' => $component->order_duration * $component->cost_per_unit
            ]);
            foreach ($component->rent_parts->where('type', 'rent') as $item) {
                $item->update([
                    'end_date' => $dateTo
                ]);
            }
        } else {
            $fields = collect(clone $component)->except('id','avito_dotation_sum')->toArray();

            // if($clone && $component->order_type !== )

            $order_type =
                $clone
                    ? $order_type
                    : $component->order_type;
            $current_date_to = $calculatedDateTo;
            /** @var Carbon $current_date_to */
//            $current_date_to = $order_type === TimeCalculation::TIME_TYPE_SHIFT
//                ? ($component->worker->change_hour === 24
//                    ? $calculatedDateTo->addMinute(1)
//                    : $calculatedDateTo->addDay(1))
//                : $calculatedDateTo->addMinutes(1);


            $dateTo = getDateTo($current_date_to, $order_type, $duration);

            if ($component->worker instanceof Machinery) {
                /** @var Machinery $vehicle */
                $vehicle = Machinery::query();
                if ($order_type === TimeCalculation::TIME_TYPE_HOUR) {
                    Machinery::query()->checkAvailable($current_date_to, $dateTo, $order_type, $duration);
                }

                $vehicle = $vehicle->find($component->worker->id);
                if (!$vehicle) {
                    $error = ValidationException::withMessages([
                        'duration' => ['В указанном периоде продления техника занята']
                    ]);

                    throw $error;
                }
            }
            $shiftDuration = (int) (request()->shift_duration ?: $this->change_hour);
            $dates = $vehicle->getDatesForOrder($current_date_to->copy(), $duration, $order_type, shiftDuration: $shiftDuration);

            if (!$dates) {
                $error = ValidationException::withMessages([
                    'duration' => ['В указанном периоде продления техника занята']
                ]);

                throw $error;
            }
            if ($vehicle->company_branch->getSettings()->price_without_vat && $this->order->contractorRequisite && $this->order->contractorRequisite->vat_system === Price::TYPE_CASHLESS_VAT) {

                $cost_per_unit = Price::addVat($cost_per_unit, $vehicle->company_branch->domain->country->vat);
                $valueAdded = Price::addVat($valueAdded, $vehicle->company_branch->domain->country->vat);

            }
            $dateTo = $order_type === TimeCalculation::TIME_TYPE_HOUR
                ? getDateTo($current_date_to, $order_type, $duration)
                : Carbon::parse($dates[count($dates) - 1]);

            if ($order_type === TimeCalculation::TIME_TYPE_SHIFT) {
                if ($shiftDuration === 24) {
                    $diffInMinutes = $current_date_to->copy()->startOfDay()->diffInMinutes($current_date_to);
                    if($diffInMinutes === 0){
                        $dateTo->endOfDay();
                    }else {
                        $dateTo->startOfDay()->addMinutes($diffInMinutes)->subMinute();
                    }
                } else {
                    if ($shift_duration){
                        if($shift_duration == 23){
                            $dateTo->endOfDay();
                        }else {
                            $currentDay = $component->worker->getScheduleForDay($dateTo->format('D'));
                            $dateTo->startOfDay()
                                ->addHours($currentDay->time_to[0])
                                ->addMinutes($currentDay->time_to[1]);
                        }
                    }
                }
            }

            if ($clone) {
                if ($this->order->customer instanceof Customer) {
                    $contract = $this->order->customer->contracts
                        ->where('requisite_id', $this->order->contractor_requisite_id)
                        ->where('requisite_type', $this->order->contractor_requisite_type)->first();
                    if (!$contract) {
                        $contract = $this->order->customer->generateContract($this->order->contractorRequisite);
                    }
                }

                $component = new OrderComponent($fields);

                $component->worker()->associate($vehicle);
                $component->fill([
                    'order_type' => $order_type,
                    'order_duration' => $duration,
                    'cost_per_unit' => $cost_per_unit,
                    'amount' => 0,
                    'delivery_cost' => 0,
                    'shift_duration' => $shiftDuration,
                    'return_delivery' => 0,
                    'comment' => null,
                    'application_id' => ($this->order->customer instanceof Customer
                        ? ($this->order->customer->getAndIncrementApplicationId($this->order->contract ?? $contract))
                        : null),
                    'date_from' => $current_date_to,
                    'date_to' => $dateTo,
                    'value_added' => $vehicle->subOwner
                        ? numberToPenny($valueAdded)
                        : 0,

                ]);
                $component->save();


            } else {

                $component->increment('order_duration', $duration);
                $component->update([
                    'amount' => 0,
                    'date_to' => $dateTo
                ]);
            }
            $vehicle->generateOrderCalendar($dates, $order_type, $duration, $component, $current_date_to, $dateTo);
        }
        /*$isDayRent = $vehicle->change_hour === 24;
        $i = 0;
        foreach ($dates as $date) {
            ++$i;
            $startDate = Carbon::parse($date);
            $endDate = $startDate->copy();

            $currentDay = $component->worker->getScheduleForDay($startDate->format('D'));

            if ($i !== 1 && !$isDayRent) {
                $startDate->startOfDay()
                    ->addHours($currentDay->time_from[0])
                    ->addMinutes($currentDay->time_from[1]);
            }
            if ($order_type === TimeCalculation::TIME_TYPE_HOUR) {
                $endDate = $dateTo;
            } else {
                if($isDayRent) {
                    $endDate->addDay();
                }else {
                    $endDate->setHour($currentDay->time_to[0]);
                    $endDate->setMinute($currentDay->time_to[1]);
                }

            }
            if ($startDate->gt($endDate)) {
                $endDate->endOfDay();
            }

            if ($i === count($dates)) {
                $endDate = $dateTo;
            }
            FreeDay::create([
                'startDate'          => $startDate,
                'endDate'            => $endDate,
                'type'               => 'order',
                'order_id'           => $this->order->id,
                'order_component_id' => $component->id,
                'machine_id'         => $vehicle->id
            ]);
        }*/

        $component->histories()->save(new OrderComponentHistory([
            'type' => ($clone
                ? 'new'
                : 'prolongation'),
            'description' => trans('contractors/edit.prolong'),

        ]));

        $companyService = new CompaniesService($component->worker->company_branch->company);

        $companyService->addUsersNotification(
            trans('user_notifications.order_vehicle_prolongation', ['id' => $component->order->internal_number]),
            Auth::user()
                ?: null,
            UserNotification::TYPE_INFO,
            $component->order->generateCompanyLink(),
            $component->worker->company_branch);


        if ($component->driver) {
            $this->telegramService->sendRefreshOrder($component);
        }
        try {
            if ($this->order->company_branch->ins_setting && $this->order->company_branch->ins_setting->active) {
                $insuranceService = new InsuranceService();
                $scoring = $insuranceService->getScoring($this->order);
                if ($scoring['result']->final_result == 0) {
                    $dateFrom = Carbon::parse($current_date_to)->addDay();
                    $insuranceService->createInsuranceCertificate($component, $scoring['type'], $dateFrom, $dateTo,
                        $duration);
                }
            }
        } catch (Exception $e) {
            Log::error('Failed to create certificate', [
                'exception' => $e->getMessage().'File: '.$e->getFile().' Line: '.$e->getLine()
            ]);
            Log::error('Data', [
                'legalRequisites' => $this->order->customer->legal_requisites,
                'individualRequisites' => $this->order->customer->individual_requisites
            ]);
        } catch (TypeError $e) {
            Log::error('Failed to create certificate', [
                'exception' => $e->getMessage().'File: '.$e->getFile().' Line: '.$e->getLine()
            ]);
            Log::error('Data', [
                'legalRequisites' => $this->order->customer->legal_requisites,
                'individualRequisites' => $this->order->customer->individual_requisites
            ]);
        }

        return $this;
    }

    function rejectApplication($orderComponentId, $rejectType = null, $remove = false)
    {
        /** @var OrderComponent $component */
        $component = $this->order->components()->where('status', Order::STATUS_ACCEPT)->findOrFail($orderComponentId);
        if(!$remove) {
            $component->update([
                'complete' => 1, 'finish_date' => now(),
                'status' => Order::STATUS_REJECT,
                'reject_type' => $rejectType
            ]);
        }else {
            $contract = $this->order->contract ?? $this->order->customer->generateContract(
                $this->order->contractorRequisite
            );
            $lastApplicationId = $contract->last_application_id;

            if($lastApplicationId === $component->application_id) {
                $contract->decrement('last_application_id');
            }
            WarehousePartsOperation::query()->where('order_worker_id', $component->id)->delete();
            $component->delete();
        }


        FreeDay::query()->where('order_component_id', $component->id)->delete();
        $docs =
            $this->order->documents()->where('name', 'like',
                "% {$component->application_id}  {$this->order->customer->company_name}%")->get();
        $docs->each(function ($item) {
            $item->delete();
        });

        if ($this->order->components()->whereIn('status',
                [Order::STATUS_REJECT])->count() === $this->order->components()->count()) {
            $this->order->update([
                'status' => Order::STATUS_CLOSE,
                'tmp_status' => Order::STATUS_REJECT,
            ]);

            if ($this->order->lead) {
                $this->order->lead->close();
            }
        }else if ($this->order->components()->whereIn('status',
                [Order::STATUS_REJECT, Order::STATUS_DONE])->count() === $this->order->components()->count()) {
            $this->order->archive();
        }

        if ($component->driver) {
            $this->telegramService->sendRefreshOrder($component);
        }

        if ($component->worker_type === WarehousePartSet::class && !$remove) {
            foreach ($component->rent_parts as $item) {
                $new = $item->replicate();
                $new->type = 'reject';
                $new->push();
            }
        }


        return $this;
    }

    function changeApplicationDuration(
        $orderComponentId,
        Carbon $dateFrom,
        $duration,
        $orderType,
        $costPerUnit,
        $deliveryCost,
        $returnDelivery,
        $valueAdded = 0,
        $services = [],
        $rentParts = []
    ) {
        /** @var OrderComponent $component */
        $component = $this->order->components()->findOrFail($orderComponentId);

        /*  if ($component->order_duration === $duration && $dateFrom->eq($component->date_from)) {
              return true;
          }*/
        $oldDateFrom = $component->date_from->copy();
        $oldDateTo = $component->date_to->copy();
        $oldCostPerUnit = $component->cost_per_unit / 100;
        $oldDeliveryCost = $component->delivery_cost / 100;
        $oldReturnDeliveryCost = $component->return_delivery / 100;

        $dateTo = getDateTo($dateFrom, $orderType, $duration, false);
        if ($component->worker_type == WarehousePartSet::class) {
            $dateTo = getDateTo($dateFrom, $orderType, $duration, false);
            $costPerUnit = numberToPenny($costPerUnit);
            $deliveryCost = numberToPenny($deliveryCost);
            $returnDelivery = numberToPenny($returnDelivery);
            $fields = [

                'order_duration' => $duration,
                'order_type' => $orderType,
                'date_from' => $dateFrom,
                'date_to' => $dateTo,
                'cost_per_unit' => $costPerUnit,
                'delivery_cost' => $deliveryCost,
                'return_delivery' => $returnDelivery,
            ];
            if (!$component->actual) {
                $fields['amount'] = 0;
            }
            $component->update($fields);
            foreach ($component->rent_parts->where('type', 'rent') as $item) {
                $item->update([
                    'begin_date' => $dateFrom,
                    'end_date' => $dateTo
                ]);
            }
            if (!empty($rentParts)) {
                $costPerUnit = 0;
                foreach ($rentParts as $rentPart) {
                    $wp = WarehousePartsOperation::query()->find($rentPart['id']);
                    $wp->cost_per_unit = numberToPenny($rentPart['cost_per_unit']);
                    $wp->save();
                    $costPerUnit += $wp->cost_per_unit * $wp->count;
                }
                $component->cost_per_unit = $costPerUnit;
                $component->amount = $costPerUnit * $component->order_duration;
                $component->save();
            }
        } else {
            $component->worker->freeDays()->where('order_component_id', $component->id)->delete();

            /** @var Machinery $vehicle */
            $vehicle = Machinery::query();
            if ($orderType === TimeCalculation::TIME_TYPE_HOUR) {
                $vehicle->checkAvailable($dateFrom, $dateTo, $component->order_type, $duration);
            }

            $vehicle = $vehicle->find($component->worker->id);

            if (!$vehicle) {
                $error = ValidationException::withMessages([
                    'duration' => ['В указанном периоде техника занята']
                ]);

                throw $error;
            }

            $dates = $vehicle->getDatesForOrder($dateFrom->copy(), $duration, $orderType, forceAddDay: true);

            $dateTo = $orderType === TimeCalculation::TIME_TYPE_HOUR
                ? getDateTo($dateFrom, $orderType, $duration)
                : Carbon::parse(last($dates))->endOfDay();

            if ($orderType === TimeCalculation::TIME_TYPE_SHIFT) {
                if ($component->worker->change_hour === 24) {
                    $diffMinutes = $dateFrom->copy()->startOfDay()->diffInMinutes($dateFrom);
                    $dateTo->startOfDay()->addMinutes($diffMinutes)->subMinute();
                } else {

                    $lastDay = $component->worker->getScheduleForDay($dateTo->format('D'));
                    $dateTo->startOfDay()
                        ->addHours($lastDay->time_to[0])
                        ->addMinutes($lastDay->time_to[1]);

                    //$dateTo->endOfDay();
                }
            }

            if ($app = $this->order->components()
                ->where('worker_id', $vehicle->id)
                ->forPeriod($dateFrom, $dateTo)
                ->whereNotIn('status', [Order::STATUS_REJECT])
                ->where('order_workers.id', '!=', $component->id)
                ->first()) {
                $error = ValidationException::withMessages([
                    'errors' => ["Приложения пересекаются. Измените даты в приложении #{$app->id}"]
                ]);

                throw $error;
            }

            $costPerUnit = numberToPenny($costPerUnit);
            $deliveryCost = numberToPenny($deliveryCost);
            $returnDelivery = numberToPenny($returnDelivery);
            $valueAdded = numberToPenny($valueAdded);
            $isVatSystem =
                $vehicle->company_branch->getSettings()->price_without_vat && $this->order->contractorRequisite && $this->order->contractorRequisite->vat_system === Price::TYPE_CASHLESS_VAT;
            if ($isVatSystem) {

                $costPerUnit = Price::addVat($costPerUnit, $vehicle->company_branch->domain->country->vat);
                $deliveryCost = Price::addVat($deliveryCost, $vehicle->company_branch->domain->country->vat);
                $returnDelivery = Price::addVat($returnDelivery, $vehicle->company_branch->domain->country->vat);
                $valueAdded = Price::addVat($valueAdded, $vehicle->company_branch->domain->country->vat);

            }

            $fields = [

                'order_duration' => $duration,
                'order_type' => $orderType,
                'date_from' => $dateFrom,
                'date_to' => $dateTo,
                'cost_per_unit' => $costPerUnit,
                'delivery_cost' => $deliveryCost,
                'return_delivery' => $returnDelivery,
                'value_added' => ($vehicle->subOwner
                    ? $valueAdded
                    : 0),
            ];
            //   if (!$component->actual) {
            $fields['amount'] = 0;
            //   }
            $component->update($fields);


            if ($services) {
                foreach ($services as $service) {
                    $fields = [
                        'price' => Price::addVat(numberToPenny($service['price']), $isVatSystem
                            ? $this->order->company_branch->domain->vat
                            : 0),
                        'value_added' => Price::addVat(numberToPenny($service['value_added'] ?? 0), $isVatSystem
                            ? $this->order->company_branch->domain->vat
                            : 0),
                        'name' => $service['name'],
                        'count' => $service['count'] ?? 1,
                        'custom_service_id' => $service['custom_service_id'],
                    ];
                    if (!empty($service['id'])) {
                        if (!toBool($service['active'])) {
                            $component->services()->where('id', $service['id'])->delete();
                        } else {
                            $component->services()->where('id', $service['id'])->update($fields);
                        }
                    } else {
                        if (toBool($service['active'] ?? false)) {
                            $component->services()->save(new OrderComponentService($fields));
                        }
                    }

                }
            }

            $i = 0;
            foreach ($dates as $date) {
                ++$i;
                $startDate = Carbon::parse($date);
                $endDate = $startDate->copy();

                $currentDay = $component->worker->getScheduleForDay($startDate->format('D'));

                if ($i !== 1) {
                    $startDate->startOfDay()
                        ->addHours($currentDay->time_from[0])
                        ->addMinutes($currentDay->time_from[1]);
                }
                if ($orderType === TimeCalculation::TIME_TYPE_HOUR) {
                    $endDate = $dateTo;
                } else {
                    $endDate->setHour($currentDay->time_to[0]);
                    $endDate->setMinute($currentDay->time_to[1]);
                }
                if ($startDate->gt($endDate)) {
                    $endDate->endOfDay();
                }

                if ($i === count($dates)) {
                    $endDate = $dateTo;
                }
                FreeDay::create([
                    'startDate' => $startDate,
                    'endDate' => $endDate,
                    'type' => 'order',
                    'order_id' => $this->order->id,
                    'order_component_id' => $component->id,
                    'machine_id' => $vehicle->id
                ]);
            }
        }

        $costPerUnit /= 100;
        $deliveryCost /= 100;
        $returnDelivery /= 100;

        $component->histories()->save(new OrderComponentHistory([
            'type' => 'change',
            'description' => "Изменение даты. {$oldDateFrom->format('d.m.Y H:i')} - {$oldDateTo->format('d.m.Y H:i')} На {$dateFrom->format('d.m.Y H:i')} - {$dateTo->format('d.m.Y H:i')}; 
            Стоимость за ед. - {$oldCostPerUnit} - {$costPerUnit}
            Доставка - {$oldDeliveryCost} - {$deliveryCost}
            Обратная доставка - {$oldReturnDeliveryCost} - {$returnDelivery}
            ",

        ]));

        if ($component->driver) {
            $this->telegramService->sendRefreshOrder($component);
        }
        try {
            if ($this->order->company_branch->ins_setting && $this->order->company_branch->ins_setting->active) {
                $insuranceService = new InsuranceService();
                $scoring = $insuranceService->getScoring($this->order);
                if ($scoring['result']->final_result == 0) {
                    $insuranceService->createInsuranceCertificate($component, $scoring['type']);
                }
            }
        } catch (Exception $e) {
            Log::error('Failed to create certificate', [
                'exception' => $e->getMessage().'File: '.$e->getFile().' Line: '.$e->getLine()
            ]);
            Log::error('Data', [
                'legalRequisites' => $this->order->customer->legal_requisites,
                'individualRequisites' => $this->order->customer->individual_requisites
            ]);
        } catch (TypeError $e) {
            Log::error('Failed to create certificate', [
                'exception' => $e->getMessage().'File: '.$e->getFile().' Line: '.$e->getLine()
            ]);
            Log::error('Data', [
                'legalRequisites' => $this->order->customer->legal_requisites,
                'individualRequisites' => $this->order->customer->individual_requisites
            ]);
        }

        return $this;
    }

    /**
     * Создание оплачиваемого заказа
     * Входящий массив с единицами техники и датой начала
     * и продолжительностью работ.
     * @param $request
     * @param  CompanyBranch  $companyBranch  компания заказчик
     * @param  User  $initiator  инициатор операции
     * @return \Illuminate\Http\JsonResponse
     * @throws \Exception
     */
    function generatePayment(
        $request,
        CompanyBranch $companyBranch,
        User $initiator
    ) {
        $vehicles = collect($request->vehicles);
        $vehicles = $vehicles->map(function ($item) {
            $item['time'] = strtotime($item['date_from']);
            return $item;
        });

        $contractor = CompanyBranch::findOrFail($request->input('contractor_id'));


        //Если заказ поступает из заявки.
        $lead = false;
        if ($request->filled('lead_id')) {
            $lead = Lead::query()->where('company_branch_id', $companyBranch->id)->findOrFail($request->lead_id);
        }


        $coordinates = explode(',', $request->coordinates);

        $collection = collect();

        $required_categories = [];

        foreach ($vehicles as $vehicle) {
            $date_from = Carbon::parse($vehicle['date_from']);

            $duration = $vehicle['order_duration'];

            $date_to = getDateTo($date_from, $vehicle['order_type'], $duration);

            $v = $contractor->machines()
                ->checkAvailable($date_from, $date_to, $vehicle['order_type'], $duration)
                ->whereInCircle($coordinates[0], $coordinates[1])
                ->sharedLock()
                ->findOrFail($vehicle['id']);

            $collection->push($v);

            $required_categories[] = [
                'order_type' => $vehicle['order_type'],
                'order_duration' => $duration,
                'type_id' => $v->type,
                'date_from' => clone $date_from,
                'order_waypoints' => $vehicle['order_waypoints'] ?? [],
                'order_params' => $vehicle['order_params'] ?? [],
            ];
        }

        $date_from = Carbon::createFromTimestamp($vehicles->min('time'));


        $lock_ids = $collection->pluck('id')->toArray();
        disableLock($lock_ids);
        $lock = checkLock($lock_ids);
        if (!$lock) {
            return response()->json(['errors' => [trans('transbaza_order.vehicle_wait_busy')]], 419);
        }
        DB::beginTransaction();

        try {

            $orderService = new OrderManagement($required_categories, $request->coordinates);

            $orderService
                ->setInitiator($initiator)
                ->setCustomer($companyBranch)
                ->setContractor($contractor);

            $pay_items = $orderService->prepareVehicles($collection);

            $orderService
                ->setDateFrom($date_from)
                ->setDetails([
                    'contact_person' => $request->contact_person,
                    'address' => $request->address,
                    'region_id' => $lead
                        ? $lead->region_id
                        : null,
                    'city_id' => $lead
                        ? $lead->city_id
                        : null,
                    'coordinates' => $request->coordinates,
                    'start_time' => $date_from->format('H:i'),
                ])
                ->createProposalForPayment();

            $for_promo = ($request->filled('promo_code') && config('in_mode'));


            $payment = Payment::create([
                'system' => ($for_promo
                    ? Payment::TYPE_PROMO
                    : $request->input('pay_type')),
                'status' => Payment::STATUS_WAIT,
                'currency' => RequestHelper::requestDomain()->currency->code,
                'amount' => $orderService->created_proposal->amount,
                'creator_id' => $initiator->id,
                'company_branch_id' => $companyBranch->id,
                'order_id' => $orderService->created_proposal->id
            ]);

            $instance = $payment->generatePayment($pay_items, $request->pay_type, $request->input('invoice'));

            if ($lead) {
                $lead->orders()->attach($orderService->created_proposal);
            }

        } catch (\Exception $exception) {
            DB::rollBack();
            disableLock($lock_ids);

            Log::info("error payment {$exception->getMessage()} {$exception->getTraceAsString()}");
            return response()->json(['error payment'], 500);
        }

        if ($instance === 'promo' || $instance === 'invoice') {
            DB::commit();
            disableLock($lock_ids);

            if ($instance === 'invoice') {
                dispatch(new SendOrderInvoice($orderService->created_proposal));
            }

            return response()->json(['order_id' => $orderService->created_proposal->id]);
        }
        if ($instance instanceof TinkoffMerchantAPI) {
            $tinkoffApi = $instance;
        }


        if (!$tinkoffApi->paymentUrl) {
            DB::rollBack();
            disableLock($lock_ids);
            return response()->json(['error'], 419);
        }

        $payment->tinkoff_payment->updateData($tinkoffApi);
        DB::commit();
        disableLock($lock_ids);
        return \response()->json([
            'url' => $tinkoffApi->paymentUrl
        ]);

    }

    function getApplicationWorkHours($orderComponentId)
    {
        /** @var OrderComponent $component */
        $component = $this->order->components()->findOrFail($orderComponentId);

        $hours = [];

        foreach ($component->calendar as $item) {
            $hours[] = [
                'time_from' => $item->startDate->format('H:i'),
                'time_to' => $item->startDate->addHours($component->worker->change_hour)->format('H:i'),
                'date' => $item->startDate->format('Y-m-d'),
                'hours' => $component->worker->change_hour,
            ];
        }

        return $hours;
    }


    function setActualApplicationData(
        $orderComponentId,
        $data
    ) {
        /** @var OrderComponent $component */
        $component = $this->order->components()->findOrFail($orderComponentId);
        $hours = [];
        if ($data['order_type'] === TimeCalculation::TIME_TYPE_HOUR) {
            $hours = collect($data['hours']);
            $hours = $hours->map(function ($item) {

                $item['stampFrom'] = Carbon::parse($item['date'].' '.$item['time_from'])->timestamp;
                $item['stampTo'] =
                    Carbon::parse($item['date'].' '.$item['time_from'])->addMinutes($item['hours'] * 60)->timestamp;

                return $item;
            });
            $dateFrom = Carbon::parse($hours->min('stampFrom'))->setTimezone(config('app.timezone'));
            $dateTo = Carbon::parse($hours->max('stampTo'))->setTimezone(config('app.timezone'));
        } else {
            $dateFrom = $component->date_from;
            $dateTo = getDateTo($dateFrom, TimeCalculation::TIME_TYPE_SHIFT, $data['order_duration']);
            $lastDay = $component->worker->getScheduleForDay($dateTo->format('D'));
            $dateTo->startOfDay()->setTimeFrom($dateFrom);
            if ($component->worker->change_hour != 24) {
                $dateTo->startOfDay()
                    ->addHours($lastDay->time_to[0])
                    ->addMinutes($lastDay->time_to[1]);
            }

        }


        $cost_per_unit = numberToPenny($data['cost_per_unit']);
        $valueAdded = numberToPenny($data['value_added']);
        $deliveryCost = numberToPenny($data['delivery_cost']);
        $returnDelivery = numberToPenny($data['return_delivery']);
        $amount = numberToPenny($data['amount'] ?? 0);
        if ($this->isVatSystem) {
            $vat = $this->order->company_branch->domain->country->vat;
            $cost_per_unit = Price::addVat($cost_per_unit, $vat);
            $valueAdded = Price::addVat($valueAdded, $vat);
            $deliveryCost = Price::addVat($deliveryCost, $vat);
            $returnDelivery = Price::addVat($returnDelivery, $vat);
            $amount = Price::addVat($amount, $vat);
        }


        $fields = [
            'amount' => $amount,
            'cost_per_unit' => $cost_per_unit,
            'date_from' => $dateFrom,
            'date_to' => $dateTo,
            'delivery_cost' => $deliveryCost,
            'order_type' => $data['order_type'],
            'order_duration' => ($data['order_type'] === TimeCalculation::TIME_TYPE_HOUR
                ? $hours->sum('hours')
                : $data['order_duration']),
            'return_delivery' => $returnDelivery,
            'value_added' => $valueAdded,
            'auto' => $data['auto'] ?? false,
        ];

        $actual = $component->actual;

        if ($actual) {
            $actual->update($fields);
        } else {
            $actual = new OrderComponentActual($fields);

            /** @var OrderComponentActual $actual */
            $actual = $component->actual()->save($actual);
        }
        $actual->services()->delete();
        $isVatSystem =
            $this->order->company_branch->getSettings()->price_without_vat && $component->contractorRequisite && $component->contractorRequisite->vat_system === Price::TYPE_CASHLESS_VAT;

        foreach ($data['services'] as $service) {
            $actual->services()->save(new OrderComponentServiceActual([
                'price' => Price::addVat(numberToPenny($service['price']), $isVatSystem
                    ? $this->order->company_branch->domain->vat
                    : 0),
                'name' => $service['name'],
                'custom_service_id' => $service['custom_service_id'],
                'count' => $service['count'] ?? 1,
                'unit_id' => $service['unit_id'] ?? null,
                'value_added' => Price::addVat(numberToPenny($service['value_added'] ?? 0), $isVatSystem
                    ? $this->order->company_branch->domain->vat
                    : 0),
            ]));
        }

        $actual->reports()->delete();

        $component->calendar()->delete();

        if ($hours && $data['order_type'] === TimeCalculation::TIME_TYPE_HOUR) {
            foreach ($hours as $hour) {
                $date = Carbon::parse($hour['date']);
                $timeFrom = $hour['time_from'];
                $duration = $hour['hours'];
                $df = Carbon::parse("{$date->format('Y-m-d')} $timeFrom");
                $dt = $df->clone()->addMinutes($duration * 60);
                $actual->reports()->save(new OrderComponentReportTimestamp([
                    'date' => $date->format('Y-m-d'),
                    'time_from' => $timeFrom,
                    'time_to' => $dt,
                    'duration' => $duration,
                    'idle_duration' => 0,
                    'cost_per_unit' => 0,

                ]));

                FreeDay::create([
                    'startDate' => $df,
                    'endDate' => $dt,
                    'type' => 'order',
                    'order_id' => $this->order->id,
                    'order_component_id' => $component->id,
                    'machine_id' => $component->worker->id
                ]);
            }

        } else {
            $dates =
                $component->worker->getDatesForOrder($component->date_from, $data['order_duration'],
                    TimeCalculation::TIME_TYPE_SHIFT);
            $date_from = Carbon::parse($dates[0]);

            //  $currentDay = $component->worker->getScheduleForDay($date_from->format('D'));

            $date_to = $dates[count($dates) - 1];

            if (is_string($date_to)) {
                $date_to = Carbon::parse($date_to);
            }
            if ($component->worker->change_hour === 24) {
                $diffMinutes = $date_from->copy()->startOfDay()->diffInMinutes($date_from);
                $date_to->startOfDay()->addMinutes($diffMinutes);
            } else {
                $endDay = $component->worker->getScheduleForDay($date_to->format('D'));
                $date_to->startOfDay()
                    ->addHours($endDay->time_to[0])
                    ->addMinutes($endDay->time_to[1]);
            }


            $i = 0;
            foreach ($dates as $date) {
                ++$i;
                $startDate = Carbon::parse($date);
                $endDate = $startDate->copy();

                $currentDay = $component->worker->getScheduleForDay($startDate->format('D'));

                if ($i !== 1) {
                    $startDate->startOfDay()
                        ->addHours($currentDay->time_from[0])
                        ->addMinutes($currentDay->time_from[1]);
                }

                $endDate->setHour($currentDay->time_to[0]);
                $endDate->setMinute($currentDay->time_to[1]);
                if ($startDate->gt($endDate)) {
                    $endDate->endOfDay();
                }
                if ($i === count($dates)) {
                    $endDate = $date_to;
                }
                FreeDay::create([
                    'startDate' => $startDate,
                    'endDate' => $endDate,
                    'type' => 'order',
                    'order_id' => $component->order_id,
                    'order_component_id' => $component->id,
                    'machine_id' => $component->worker->id
                ]);
            }
        }

    }

    /**
     * @param OrderComponent $component
     * @param $duration
     * @param null $shift_duration
     * @return Carbon
     */
    private function getDateTo(OrderComponent $component, $duration, $shift_duration = null): Carbon
    {
        $dateTo = Carbon::parse($component->date_to);

        if ($component->order_type == TimeCalculation::TIME_TYPE_HOUR) {
            $dateTo = Carbon::parse($component->date_to)->addHours($duration);
        } elseif ($component->order_type == TimeCalculation::TIME_TYPE_SHIFT) {

            switch ($shift_duration){
                case 23: $dateTo = Carbon::parse($component->date_to)->addDays($duration)->endOfDay();  break;
                case 24: $dateTo = Carbon::parse($component->date_to)->addDays($duration);  break;
                case 8: $dateTo = Carbon::parse($component->date_to)->addDays($duration);   break;
                default: $dateTo = Carbon::parse($component->date_to)->addDays($duration); break;
            }
        }
        return $dateTo;
    }

    private function checkDates( $component, Carbon $dateTo)
    {
        $start = Carbon::parse($component->date_to)->addMinutes();

        $count = OrderComponent::query()
            ->where('worker_id', $component->worker_id)
            ->where('worker_type', get_class($component->worker))
            ->where('date_from', '<', $dateTo)
            ->where('date_to', '>', $start)
            ->count();

        if ($count > 0) {
            return false;
        }
        return true;
    }

}
