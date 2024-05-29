<?php

namespace App\Http\Controllers\Avito\Repositories;

use App\Http\Controllers\Avito\Dto\CreateOrderConditions;
use App\Http\Controllers\Avito\Exceptions\CustomerNotFound;
use App\Http\Controllers\Avito\Exceptions\MachineryNotFound;
use App\Http\Controllers\Avito\Models\AvitoHoldsHistory;
use App\Http\Controllers\Avito\Models\AvitoOrder;
use App\Http\Controllers\Avito\Models\AvitoOrderHistory;
use App\Http\Controllers\Avito\Models\AvitoStat;
use App\Machinery;
use App\Overrides\Model;
use App\Service\DaData;
use App\User\IndividualRequisite;
use Cache;
use Carbon\Carbon;
use Modules\CompanyOffice\Entities\Company\CompanyBranch;
use Modules\CompanyOffice\Entities\Company\Contact;
use Modules\CompanyOffice\Entities\Company\ContactEmail;
use Modules\CompanyOffice\Entities\Company\ContactPhone;
use Modules\CompanyOffice\Entities\Company\DocumentsPack;
use Modules\ContractorOffice\Services\Tariffs\TimeCalculation;
use Modules\Dispatcher\Entities\Customer;
use Modules\Orders\Entities\MachineryStamp;
use Modules\Orders\Entities\Order;
use Modules\Orders\Entities\OrderComponent;
use Modules\Orders\Entities\Payment;
use Modules\RestApi\Entities\Domain;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Psr\SimpleCache\InvalidArgumentException;

class AvitoRepository implements IIntegrationRepository
{

    private Logger $logger;

    public function __construct(private Domain $domain)
    {
        $this->domain = Domain::query()->where('alias', 'ru')->first();
        $this->logger = new Logger('integration-api');
        $this->logger->pushHandler(new StreamHandler(
            storage_path('logs/integration-api/' . now()->format('Y-m-d') . '.log'),Logger::DEBUG,true,0777
        ));
    }

    public function addCustomer(
        CreateOrderConditions $conditions,
        CompanyBranch         $companyBranch,
                              $customerInfo
    ): Customer|Model
    {
        $customerConditions = $conditions->customer;


        $customer = Customer::query()->updateOrCreate([
            'phone' => $customerConditions->phone,
            'company_branch_id' => $companyBranch->id,
        ], [
            'name' => $customerConditions->inn ? $customerInfo->value : $customerConditions->name,
            'address' => $customerConditions->inn ? $customerInfo->data->address->value : null,
            'contact_person' => $customerConditions->inn ? $customerInfo->value : $customerConditions->name,
            'company_name' => $customerConditions->inn ? $customerInfo->value : $customerConditions->name,
            'email' => $customerConditions->email,
            'domain_id' => $this->domain->id,

        ]);

//        $customer->addContacts([
//            [
//                'contact_person' => $customerConditions->name,
//                'email' => $customerConditions->email,
//                'phone' => $customerConditions->phone,
//            ]
//        ]);


        return $customer;
    }

    public function findMachinery(string $avito_id, string $avitoOrderId, Carbon $date_from, int $order_duration, int $offset, bool $dayAdded = false, $exclude = []): array
    {
        $excludeBranchesIds = AvitoOrderHistory::query()->whereHas('avito_order', function ($q) use ($avitoOrderId) {
            $q->where('avito_order_id', $avitoOrderId);
        })->where('timeout_cancel', '>', 1)->get()->pluck('company_branch_id')->toArray();

        $avitoOrder = AvitoOrder::query()->where('avito_order_id', $avitoOrderId)->first();

        $machineries = Machinery::query()
            ->with([
                'company_branch.avito_stat'
            ])
            ->whereHas('avito_ads', function ($q) use ($exclude, $avito_id) {
                $q->where('avito_id', $avito_id);
                if (!empty($exclude)) {
                    $q->whereNotIn('machinery_id', $exclude);
                }
            })
            ->whereNotIn('company_branch_id', $excludeBranchesIds)
            ->orderBy(AvitoOrderHistory::select('timeout_cancel')
                ->whereColumn('avito_order_histories.company_branch_id', 'machineries.company_branch_id')
                ->where('avito_order_histories.avito_order_id', $avitoOrder->id)
                ->latest()
                ->take(1)
            )
            ->orderBy(AvitoStat::select('orders_count')
                ->whereColumn('avito_stats.company_branch_id', 'machineries.company_branch_id')
                ->latest()
                ->take(1)
            );
        $this->logger->info('======================================================================>>>');
        $this->logger->info('Machinery query: ' . $machineries->toSql());
        $this->logger->info('Machinery query: ', $machineries->getBindings());
        $this->logger->info('======================================================================>>>');
        $virtual  = config('avito.virtual');
        if($virtual){
            $virtualId = config('avito.virtual_company_id');
            $machineries = $machineries->where('company_branch_id', $virtualId);
        }

        $machineries = $machineries->latest('created_at')->get();

        $result = null;
        foreach ($machineries as $machinery) {
            try {
                $dates = $machinery->getDatesForOrder($date_from->copy(), $order_duration, TimeCalculation::TIME_TYPE_SHIFT, $offset);

                if (!empty($dates[0])) {
                    $result = $machinery;
                    break;
                }
            } catch (\Exception $e) {
                $exceptionMessage = $e->getMessage();
                continue;
            }
        }

        if ($result) {
            return [$result, $dates[0]];
        }
        if ($dayAdded) {
            if (empty($exclude)) {
                throw new MachineryNotFound('Техника не найдена');
            } else {
                return [null, null];
            }
        }

        return $this->findMachinery($avito_id, $avitoOrderId, $date_from->addDay()->setTime(17, 0, 0), $order_duration, $offset, true, $exclude);
    }

    public function findAllAvailableMachinery(string $avito_id, string $avitoOrderId, Carbon $date_from, int $order_duration, int $offset)
    {
        $data = null;
        $exclude = [];
        $machinery = true;
        while (!empty($machinery)) {
            [$machinery, $newDate] = $this->findMachinery($avito_id, $avitoOrderId, $date_from, $order_duration, $offset, exclude: $exclude);
            if ($machinery) {
                $exclude[] = $machinery->id;
                $data[] = [$machinery, $newDate];
            }
        }
        return $data;
    }

    public function attachDocuments(CreateOrderConditions $conditions, Customer|Model $customer, $customerInfo): array
    {
        $companyBranch = $customer->company_branch;

        $data = [
            'company_branch_id' => $companyBranch->id,
        ];

        if (empty($conditions->customer->inn)) {
            $data['type'] = IndividualRequisite::TYPE_PERSON;
            $data['firstname'] = $conditions->customer->name;

            $requisites = $customer->addIndividualRequisites($data);
            $typeTo = 'person';
        } else {
            $data['inn'] = $conditions->customer->inn;
            $data['kpp'] = $customerInfo->data->kpp;
            $data['name'] = $customerInfo->value;
            $data['register_address'] = $customerInfo->data->address->value;
            $data['actual_address'] = $customerInfo->data->address->value;
            $data['short_name'] = $customerInfo->value;
            $requisites = $customer->addLegalRequisites($data);
            $typeTo = 'legal';
        }

        $documents_pack_id = DocumentsPack::query()->forBranch($companyBranch->id)->where('type_to',
            $typeTo)->first()?->id;
        if(!$documents_pack_id) {
            throw new \Exception('Документы не найдены у филиала '. $companyBranch->name. '. Тип документов: '.$typeTo);
        }
        return [$requisites, $documents_pack_id];
    }

    public function createOrder(
        CreateOrderConditions $conditions,
        mixed                 $documents_pack_id,
        Customer|Model        $customer,
        Machinery|Model       $machinery
    ): Order|Model
    {

        $orderDateFrom = Carbon::parse($conditions->dates->start_date_from);
        $companyBranch = $customer->company_branch;
        $employee = $companyBranch->employees()->where('sms_notify', true)->first();
        $order = Order::query()->create([
            'type' => 'dispatcher',
            'external_id' => $conditions->avito_order_id,
            'amount' => 0,
            'company_branch_id' => $companyBranch->id,
            'date_from' => $orderDateFrom,
            'creator_id' => $employee->id,
            'user_id' => $employee->id,
            'contact_person' => $conditions->customer->name,
            'address' => trim($conditions->geo->rent_address),
            'start_time' => $orderDateFrom->format('H:i:s'),
            'status' => Order::STATUS_OPEN,
            'comment' => $conditions->comment,
            'domain_id' => $this->domain->id,
            'coordinates' => "{$conditions->geo->coordinate_y},{$conditions->geo->coordinate_x}",
            'machinery_base_id' => $machinery->base_id,
            'documents_pack_id' => $documents_pack_id,
            'contractor_id' => $companyBranch->id,
            'channel' => 'avito',
            'from' => $conditions->from,
        ]);
        $this->logger->debug('Customer', [
            '$customer' => $customer,
        ]);
        $order->customer()->associate($customer);
        $requisites = $companyBranch->requisite->first();
        $this->logger->debug('Requisites', [
            '$requisites' => $requisites,
        ]);
        $order->contractorRequisite()->associate($requisites);
        $order->save();
        $customer->generateContract($requisites);

        return $order;

    }

    /**
     * @param CreateOrderConditions $conditions
     * @param Order|Model $order
     * @param Model|Machinery $machinery
     * @return OrderComponent
     */
    public function attachMachinery(
        CreateOrderConditions $conditions,
        Order|Model           $order,
        Model|Machinery       $machinery,
        int                   $offset
    ): OrderComponent
    {

        $componentDateFrom = Carbon::parse($conditions->dates->start_date_from);

        $gridPrices = $machinery->prices()->with(['gridPrices'])->whereHas('unitCompare', function ($q) {
            return $q->where('type', 'shift');
        })->first()->gridPrices;
        $cpu = $gridPrices->where('price_type', 'cashless_vat')->first()->price;

        if (empty($cpu)) {
            $cpu = $gridPrices->where('price_type', 'cashless_without_vat')->first()->price;
        }

        $component = $order->attachCustomVehicles(
            $machinery->id,
            $cpu,
            $componentDateFrom,
            'shift',
            $conditions->dates->rental_duration,
            offset: $offset,
            application_id: 1
        );

        MachineryStamp::createTimestamp($machinery->id, $order->id, 'on_the_way',
            $component->date_from, $order->coordinates);

        $order->amount = $cpu * $conditions->dates->rental_duration;
        $order->save();

        return $component;
    }

    public function attachContacts(
        CreateOrderConditions $conditions,
        Customer|Model        $customer,
        Order|Model           $order
    ): Contact|Model
    {
        $companyBranch = $customer->company_branch;

        $contact = Contact::query()->create([
            'contact_person' => $customer->name,
            'position' => trans('calls/calls.main'),
            'company_branch_id' => $companyBranch->id,
        ]);

        if ($order->principal) {
            $order->contacts()->attach($order->principal->person->id);
        }

        $contact->phones()->save(new ContactPhone(['phone' => $customer->phone ?? null]));
        $contact->emails()->save(new ContactEmail(['email' => $customer->email ?? null]));
        return $contact;
    }

    public function addPayment(CreateOrderConditions $conditions, Order|Model $order): void
    {
        Payment::query()->create([
            'system' => 'dispatcher',
            'status' => Payment::STATUS_WAIT,
            'currency' => $this->domain->currency->code,
            'amount' => $order->amount,
            'company_branch_id' => $order->company_branch_id,
            'order_id' => $order->id
        ]);
    }

    /**
     * @param AvitoOrder $avitoOrder
     * @return void
     */
    public  static function updateHoldHistory(AvitoOrder $avitoOrder): void
    {
        if ($avitoOrder->hold != $avitoOrder->getOriginal('hold')) {
            AvitoHoldsHistory::query()->create([
                'avito_order_id' => $avitoOrder->id,
                'hold' => $avitoOrder->hold,
                'old_order_id' => $avitoOrder->getOriginal('order_id'),
                'new_order_id' => $avitoOrder->order_id,
                'type' => ($avitoOrder->hold > $avitoOrder->getOriginal('hold')) ? AvitoHoldsHistory::TYPE_IN : AvitoHoldsHistory::TYPE_OUT,
            ]);
        }
    }

}
