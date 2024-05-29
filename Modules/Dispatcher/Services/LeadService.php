<?php

namespace Modules\Dispatcher\Services;

use App\Finance\TinkoffMerchantAPI;
use App\Helpers\RequestHelper;
use App\Machinery;
use App\Machines\Type;
use App\Support\Gmap;
use App\User;
use Carbon\Carbon;
use http\Exception\InvalidArgumentException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Modules\CompanyOffice\Entities\Company\CompanyBranch;
use Modules\CompanyOffice\Entities\Company\DocumentsPack;
use Modules\CompanyOffice\Services\CompaniesService;
use Modules\ContractorOffice\Entities\System\Tariff;
use Modules\ContractorOffice\Entities\Vehicle\Price;
use Modules\ContractorOffice\Services\Tariffs\TimeCalculation;
use Modules\Dispatcher\Entities\Customer;
use Modules\Dispatcher\Entities\Lead;
use Modules\Dispatcher\Entities\LeadOffer;
use Modules\Dispatcher\Entities\LeadPosition;
use Modules\Dispatcher\Http\Requests\AcceptDispatcherOffer;
use Modules\Dispatcher\Http\Requests\AcceptOffer;
use Modules\Dispatcher\Http\Requests\CreateMyOrderFromLead;
use Modules\Dispatcher\Http\Requests\SelectContrator;
use Modules\Integrations\Entities\Telpehony\TelephonyCallHistory;
use Modules\Orders\Entities\Order;
use Modules\Orders\Jobs\SendOrderInvoice;
use Modules\Profiles\Entities\UserNotification;
use Modules\RestApi\Transformers\VehicleSearch;

class LeadService
{
    /** @var Lead */
    private $lead;

    private $createdPositions = [];

    private $needDefaultContract = false;

    private $customer;

    private $dateFrom;
    private $source = null;

    private $leadAttributes;


    function updateLead(
        Lead $lead,
             $data)
    {

        if (!$this->customer) {
            $this->setCustomer($lead->customer);
        }
        // $data['creator_id'] = $lead->user_id;
        $data['company_branch_id'] = $lead->company_branch_id;
        $this->setLeadAttributes($data);

        $this->lead = $lead;

        $this->lead->update($this->leadAttributes);

        $this->lead->positions()->delete();

        $this->leadProcess($data, true);

        $companyService = new CompaniesService($lead->company_branch->company);

        $companyService->addUsersNotification(
            trans('user_notifications.proposal_updated', ['id' => $this->lead->internal_number]),
            Auth::user()
                ?: null,
            UserNotification::TYPE_INFO,
            $this->lead->generateCompanyLink(),
            $lead->company_branch);

        return $this;
    }

    function createNewLead(
        $data,
        $company_branch_id,
        $creator_id)
    {
        $data['company_branch_id'] = $company_branch_id;
        $data['creator_id'] = $creator_id;

        $this->setLeadAttributes($data);

        $this->leadProcess($data);

        $companyService = new CompaniesService($this->lead->company_branch->company);
        $companyService->addUsersNotification(
            trans('user_notifications.proposal_created', ['id' => $this->lead->internal_number]),
            Auth::user()
                ?: null,
            UserNotification::TYPE_SUCCESS,
            $this->lead->generateCompanyLink(),
            $this->lead->company_branch);

        return $this;
    }

    private function leadProcess(
        $data,
        $update = false)
    {

        $lead = $update
            ? $this->lead
            : $this->createLead();

        if (!$update || $this->customer) {

            $lead->customer()->associate($this->customer);
        }
        if (!empty($data['documents_pack_id'])) {
            $pack = DocumentsPack::query()->forBranch($lead->company_branch_id)->findOrFail($data['documents_pack_id']);
            $lead->documentsPack()->associate($pack);
        }
        $lead->save();
        if (!empty($data['contractor_requisite_id'])) {

            $reqData = explode('_', $data['contractor_requisite_id']);
            if ($req = $lead->company_branch->findRequisiteByType($reqData[1], $reqData[0])) {
                $lead->contractorRequisite()->associate($req);
                $lead->save();
            }
        }

        $lead->addContacts($data['contacts'] ?? []);

        foreach ($data['vehicles_categories'] as $category) {


            if (!$this->dateFrom) {
                $date = Carbon::parse($category['date_from'])->format('Y-m-d');
                $time = Carbon::parse($category['start_time'])->format('H:i');
            }


            $v_category = Type::query()->findOrFail($category['id']);
            $isMonth = toBool($category['is_month'] ?? null);
             if($isMonth) {
                 $category['order_duration'] = $category['month_duration'] * $category['order_duration'];
             }
            $leadPosition = new LeadPosition([
                'type_id'               => $category['id'],
                'lead_id'               => $lead->id,
                'order_type'            => $category['order_type'] !== 'shift'
                    ? 'hour'
                    : $category['order_type'],
                'order_duration'        => $category['order_duration'],
                'count'                 => $category['count'],
                'machinery_model_id'    => $category['machinery_model_id'] ?? null,
                'warehouse_part_set_id' => $category['warehouse_part_set_id'] ?? null,
                'is_month' => $category['is_month'] ?? null,
                'month_duration' => $category['month_duration'] ?? null,
                'optional_attributes' => $category['optional_attributes'] ?? null,
                'date_from'             => $this->dateFrom
                    ?: Carbon::parse("{$date} {$time}")
            ]);
            $this->needDefaultContract = true;
            if (in_array($category['order_type'], [Tariff::CONCRETE_MIXER, Tariff::DISTANCE_CALCULATION])) {


                $route = Gmap::calculateRoute($category['coordinates'], $lead->coordinates);

                if (!$route) {
                    throw new \InvalidArgumentException();
                }
                $leadPosition->order_duration =
                    round(($route['duration']['value'] / 60) / 60, 0, PHP_ROUND_HALF_DOWN) + 1;
                $coordinates = explode(',', $category['coordinates']);
                $leadPosition->waypoints = [
                    'address'     => $category['waypoint'],
                    'coordinates' => [
                        'lat' => $coordinates[0],
                        'lng' => $coordinates[1],
                    ],
                    'distance'    => $route['distance']['value'],
                    'duration'    => $route['duration']['value'],
                ];
                if (!empty($category['params']) && $v_category->tariffs->isNotEmpty()) {

                    $leadPosition->params = $category['params'];

                }
            }
            $leadPosition->save();
            if (!empty($category['vehicle_id'])) {
                if ($v = Machinery::forBranch($this->lead->company_branch_id)->find($category['vehicle_id'])) {
                    $leadPosition->vehicles()->syncWithoutDetaching($v->id);
                }
            }
            $this->createdPositions[] = $leadPosition;

        }

        $first = $lead->fresh()->positions()->orderBy('date_from')->first();

        $lead->update([
            'start_date' => $first->date_from,
        ]);

        if ($this->needDefaultContract) {
            $this->createDefaultContract();
        }

        return $this;
    }

    private function createDefaultContract()
    {


        if ($this->lead->documentsPack && $this->lead->documentsPack->default_contract_url) {

            $path = config('app.upload_tmp_dir') . "/{$this->lead->id}_lead_contract.docx";

            $contract = Storage::disk()->get($this->lead->documentsPack->default_contract_url);

            Storage::disk()->put($path, $contract);

            $this->lead->addContract(trans('transbaza_order.contract'), $path);

            // $this->lead->addContract('Договор', $path);
        };
        //  $contract = Storage::disk('public_disk')->get('documents/default_contract.docx');


        return $this;
    }


    private function createLead()
    {
        return $this->lead = new Lead($this->leadAttributes);
    }

    /**
     * @return mixed
     */
    public function getLead()
    {
        return $this->lead;
    }


    public function setDispatcherCustomer(Customer $customer): self
    {
        $this->customer = $customer;

        return $this;
    }

    public function setCustomer(CompanyBranch $customer): self
    {
        $this->customer = $customer;

        return $this;
    }

    function setSource($source)
    {
        $this->source = $source;

        return $this;
    }


    /**
     * Подтверждение предложения в заявке от исполнителя.
     * Создается оплачиваемы заказ. Данный метод только для клиентской заявки
     * @param Lead $lead
     * @param  $request
     * @return \Illuminate\Http\JsonResponse
     * @throws \Exception
     */
    static function acceptOffer(
        Lead $lead,
             $request)
    {
        /** @var LeadOffer $offer */
        $offer = $lead->offers()->where('accept', 0)->findOrFail($request->offer_id);


        $count = $offer->positions->where('worker_type', Machinery::class)->count();

        // Проверяем доступность техники исходя их требуемых позиций

        $vehicles = $offer->getAvailableVehicles();

        // проверка на то, что вся техника из предложения в наличии.
        if ($vehicles->count() !== $count) {

            return response()->json(['message' => [trans('transbaza_order.vehicle_busy')]], 400);
        }

        $lock_ids = $vehicles->pluck('id')->toArray();

        // Блокируем технику на время выполнения запроса оформления заказа.
        $lock = checkLock($lock_ids);
        if (!$lock) {
            return response()->json(['errors' => [trans('transbaza_order.vehicle_wait_busy')]], 419);
        }

        try {
            DB::beginTransaction();

            $response = $offer->accept($request->input('pay_type'));

            $payment = $response['payment'];
            $order = $response['order'];
            $pay_items = $response['pay_items'];

            $lead->orders()->attach($order);

            $instance = $payment->generatePayment($pay_items, $request->input('pay_type'), $request->input('invoice'));


        } catch (\Exception $exception) {
            DB::rollBack();
            disableLock($lock_ids);

            Log::info("error payment {$exception->getMessage()} {$exception->getTraceAsString()}");
            return response()->json(['error payment'], 500);
        }

        if ($instance === 'invoice') {
            DB::commit();
            disableLock($lock_ids);

            if ($instance === 'invoice') {
                dispatch(new SendOrderInvoice($order));
            }

            return response()->json(['order_id' => $order->id]);
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


    /**
     * Подтверждение предлоежния от исполнителя для диспетчерской заявки.
     * Диспетчер может указывать добавленные стоимости для любой позиции.
     * @param Lead $lead
     * @param $request
     * @return \Illuminate\Http\JsonResponse
     * @throws \Exception
     */
    static function acceptDispatcherOffer(
        Lead $lead,
             $request)
    {
        $offer = $lead->offers()->where('accept', 0)->findOrFail($request->offer_id);

        // Проверяем доступность техники исходя их требуемых позиций

        $count = $offer->positions->where('worker_type', Machinery::class)->count();

        $vehicles = $offer->getAvailableVehicles();

        // проверка на то, что вся техника из предложения в наличии.
        if ($vehicles->count() !== $count) {

            return response()->json(['message' => [trans('transbaza_order.vehicle_busy')]], 400);
        }

        DB::beginTransaction();

        $order =
            $offer->acceptForDispatcher($request->input('value_added')
                ?: []);

        DB::commit();

        return response()->json([
            'order_id' => $order->id
        ]);
    }


    /**
     * Создание диспетчерского заказа на собственную технику диспетчера или его подрядчиков
     * @param Lead $lead
     * @param  $request
     * @return \Illuminate\Http\JsonResponse
     * @throws \Exception
     */
    static function createDispatcherOrder(
        Lead    $lead,
        Request $request)
    {
        if (!$lead->contractorRequisite || !$lead->documentsPack) {
            $error = ValidationException::withMessages([
                'errors' => ["Для создания сделки необходимо выбрать в заявке реквизиты и пакет документов."]
            ]);

            throw $error;
        }


        $lock = Cache::lock("lead_{$lead->id}", 10);

        DB::beginTransaction();

        try {

            if ($lock->get()) {

                $requisite =
                    $lead->contractorRequisite;//$lead->company_branch->findRequisiteByType($lead->contractor_requisite_id, $lead->contractor_requisite_type);

                $order = $lead->createMyOrder($request->input('cart'), $requisite);
                DB::commit();
                $lock->release();
            }

        } catch (ValidationException $exception) {
            $lock->release();
            DB::rollBack();
            return response()->json($exception->errors(), 400);
        } catch (\Exception $exception) {
            $lock->release();
            DB::rollBack();
            $code = 500;
            Log::error($exception->getMessage() . " " . $exception->getTraceAsString());
            if ($exception instanceof ValidationException) {

            }

        }


        return response()->json(['order_id' => $order->id ?? false], $code ?? 200);
    }


    /**
     * Создание диспетчерского заказа на технику исполнителей ТРАНСБАЗЫ
     * @param Lead $lead
     * @param $request
     * @return \Illuminate\Http\JsonResponse
     * @throws \Exception
     */
    static function selectContractor(
        Lead $lead,
             $request)
    {
        $lock = Cache::lock("lead_{$lead->id}");
        $contractor = CompanyBranch::findOrFail($request->contractor_id);

        DB::beginTransaction();

        try {
            if ($lock->get()) {
                $order = $lead->createOrder($contractor, $request->input('vehicles'));

                DB::commit();

                $lock->release();
            }


        } catch (\Exception $exception) {
            $lock->release();
            DB::rollBack();
            $code = 500;
            Log::error($exception->getMessage() . " " . $exception->getTraceAsString());

        }

        return response()->json(['order_id' => $order->id ?? false], $code ?? 200);
    }

    /**
     * @param mixed $dateFrom
     */
    public function setDateFrom(Carbon $dateFrom)
    {
        $this->dateFrom = $dateFrom;

        return $this;
    }

    /**
     * @param mixed $leadAttributes
     * @param bool $clientLead
     * @return LeadService
     */
    public function setLeadAttributes($leadAttributes)
    {
        $clientLead = $this->customer instanceof CompanyBranch;

        $this->leadAttributes = [
            'customer_name'     => $clientLead
                ? $this->customer->name
                : ($leadAttributes['contact_person'] ?? ($this->customer->contact_person
                        ?: $this->customer->company_name)),
            'title'             => $leadAttributes['title'],
            'phone'             => $leadAttributes['phone'],
            'email'             => $leadAttributes['email'] ?? '',
            'is_fast_order'     => $leadAttributes['is_fast_order'] ?? false,
            'address'           => $leadAttributes['address'],
            'comment'           => $leadAttributes['comment'] ?? null,
            'object_name'       => $leadAttributes['object_name'] ?? null,
            'source'            => $this->source,
            'pay_type'          => ($clientLead
                ? Price::TYPE_CASHLESS_WITHOUT_VAT
                : $leadAttributes['pay_type']),
            'city_id'           => $leadAttributes['city_id'],
            'status'            => Lead::STATUS_OPEN,
            'region_id'         => $leadAttributes['region_id'],
            'publish_type'      => ($clientLead
                ? Lead::PUBLISH_ALL_CONTRACTORS
                : $leadAttributes['publish_type']),
            'creator_id'        => $leadAttributes['creator_id'],
            'company_branch_id' => $leadAttributes['company_branch_id'],
            'domain_id'         => RequestHelper::requestDomain()->id,
            'coordinates'       => $leadAttributes['coordinates'],
            'customer_contract_id' => $leadAttributes['contract_id'] ?? null,
        ];

        return $this;
    }

    function attachCall($callId)
    {
        /** @var TelephonyCallHistory $call */
        $call =
            $callId instanceof TelephonyCallHistory
                ?: TelephonyCallHistory::query()->forCompany($this->lead->company_branch->company->id)->find($callId);

        if ($call) {
            $call->bind()->associate($this->lead);

            $call->save();
        }

        return $this;
    }

    function attachMail($email_uuid)
    {
        if (!$email_uuid) {
            return false;
        }
        if ($this->lead->company_branch->mailConnector) {
            try {
                $this->lead->company_branch->mailConnector->bindMail($this->lead, $email_uuid);

            } catch (\Exception $exception) {

            }

        }

        return $this;
    }

    /**
     * @return array
     */
    public function getCreatedPositions(): array
    {
        return $this->createdPositions;
    }


    static function getMachineriesForPosition(
        Lead $lead,
             $positionId,
             $excludeIds = [],
             $maxOffset = null)
    {
        $position = $lead->positions()->findOrFail($positionId);

        /** @var Builder $machines */
        $machines = $lead->company_branch->machines()
            ->with('defaultBase')
            ->sold(false)
            ->categoryBrandModel($position->type_id, $position->brand_id, $position->machinery_model_id)
            ->whereInCircle($lead->coords['lat'], $lead->coords['lng']);

        $arr = [];
        $machines =
            self::getMachineriesForPeriod($machines, $position->date_from, $position->order_type, $position->order_duration, $excludeIds, $maxOffset)
                ->map(function ($machine) use
                (
                    $position,
                    $lead,
                    &
                    $arr
                ) {

                    $machine->sum_day =
                        $machine->getSumByPriceType(TimeCalculation::TIME_TYPE_SHIFT, ($lead->contractorRequisite
                            ? $lead->contractorRequisite->vat_system
                            : Price::TYPE_CASH));
                    $machine->sum_hour =
                        $machine->getSumByPriceType(TimeCalculation::TIME_TYPE_HOUR, ($lead->contractorRequisite
                            ? $lead->contractorRequisite->vat_system
                            : Price::TYPE_CASH));
                    $arr[] = $machine;
                    return $machine;
                });

        return $arr;

    }

    public static function getMachineriesForPeriod(
        $machines,
        Carbon $dateFrom,
        $orderType,
        $duration,
        $excludeIds = [],
        $maxOffset = null)
    {
        $dateTo = getDateTo($dateFrom, $orderType, $duration);

        if ($orderType === TimeCalculation::TIME_TYPE_HOUR) {

            $machines->checkAvailable($dateFrom, $dateTo, $orderType, $duration);
        }

        if ($excludeIds) {
            $machines = $machines->whereNotIn('id', $excludeIds);
        }
        $machines = $machines->get();


        $maxOffset =
            $maxOffset
                ?: $duration * 4;
        $machines = $machines->map(/**
         * @param Machinery $machine
         */ function ($machine) use
        (
            $dateFrom,
            $duration,
            $dateTo,
            $orderType,
            $maxOffset
        ) {

            $machine->order_dates = $machine->getDatesForOrder($dateFrom->copy(), $duration, $orderType, $maxOffset);
            $machine->order = Order::query()
                ->whereHas('components', fn(Builder $builder) => $builder->where($builder->qualifyColumn('worker_id'), $machine->id)
                    ->where($builder->qualifyColumn('worker_type'), Machinery::class)
                    ->whereNotIn($builder->qualifyColumn('status'), ['reject', 'rejected'])
                    ->forPeriod($dateFrom->copy(), getDateTo($dateFrom->copy(), $orderType, $duration))
                )
                ->first();


            return $machine;

        })->filter(function ($item) use
        (
            $maxOffset
        ) {
            if (!count($item->order_dates))
                return false;

            $firstDate = Carbon::parse($item->order_dates[0]);
            $lastDate = Carbon::parse($item->order_dates[count($item->order_dates) - 1]);
            return $lastDate->diffInDays($firstDate) + 1 < $maxOffset;
        });

        return VehicleSearch::collection($machines);
    }
}

