<?php

namespace App\Http\Controllers\Avito\Services;

use App\Http\Controllers\Avito\Dto\CreateOrderConditions;
use App\Http\Controllers\Avito\Events\OrderChangedEvent;
use App\Http\Controllers\Avito\Events\OrderFailedEvent;
use App\Http\Controllers\Avito\Exceptions\CustomerNotFound;
use App\Http\Controllers\Avito\Exceptions\GeoServiceUnavailable;
use App\Http\Controllers\Avito\Exceptions\MachineryNotFound;
use App\Http\Controllers\Avito\Models\AvitoLog;
use App\Http\Controllers\Avito\Models\AvitoOrder;
use App\Http\Controllers\Avito\Models\AvitoOrderHistory;
use App\Http\Controllers\Avito\Models\AvitoStat;
use App\Http\Controllers\Avito\Repositories\AvitoRepository;
use App\Http\Controllers\Avito\Requests\CancelOrderRequest;
use App\Http\Controllers\Avito\Requests\GetOrderRequest;
use App\Http\Controllers\Avito\Requests\SupportRequest;
use App\Http\Controllers\Avito\Resources\OrderResource;
use App\Http\Controllers\Machinery\MachineryController;
use App\Jobs\AvitoNotificaion;
use App\Machinery;
use App\Machines\Brand;
use App\Machines\MachineryModel;
use App\Machines\Type;
use App\Overrides\Model;
use App\Service\DaData;
use App\Service\RequestBranch;
use App\Support\Gmap;
use App\Support\Region;
use App\System\Audit;
use Cache;
use Carbon\Carbon;
use DB;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Mail;
use Modules\CompanyOffice\Entities\Company\CompanyBranch;
use Modules\ContractorOffice\Http\Controllers\VehiclesController;
use Modules\ContractorOffice\Http\Requests\CreateVehicle;
use Modules\ContractorOffice\Services\VehicleService;
use Modules\Dispatcher\Entities\Customer;
use Modules\Dispatcher\Entities\Lead;
use Modules\Dispatcher\Http\Controllers\InvoiceController;
use Modules\Orders\Entities\Order;
use Modules\Orders\Services\OrderService;
use Modules\RestApi\Entities\Domain;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Psr\SimpleCache\InvalidArgumentException;

class AvitoService implements IIntegrationService
{

    private readonly AvitoRepository $avitoRepository;
    private readonly Domain $domain;

    public function __construct()
    {
        $this->domain = Domain::whereAlias('ru')->first();
        $this->avitoRepository = new AvitoRepository($this->domain);
        $this->logger = new Logger('integration-api');
        $this->logger->pushHandler(new StreamHandler(
            storage_path('logs/integration-api/' . now()->format('Y-m-d') . '.log'),Logger::DEBUG,true,0777
        ));
    }

    public function createOrder(CreateOrderConditions $conditions, string $url): void
    {
        $this->logger->debug('Service create order started');
        list($avitoOrder, $log) = $this->init($conditions, $url);

        try {
            $oldDate = $conditions->dates->start_date_from;
            DB::beginTransaction();
            if (env('FAKE_COORDS', false)) {
                $coordinates = [
                    'lat' => 55.916832,
                    'lng' => 36.858622,
                ];
            } else {
                $gmap = new Gmap();
                $coordinates = $gmap->getGeometry($conditions->geo->rent_address);
            }
            if (!$coordinates) {
                throw new GeoServiceUnavailable('Сервис геолокации временно не доступен');
            }
            $conditions->geo->coordinate_x = $coordinates['lng'];
            $conditions->geo->coordinate_y = $coordinates['lat'];


            $dtsFrom = Carbon::parse($conditions->dates->start_date_from);
            $dtsTo = Carbon::parse($conditions->dates->start_date_to);
            $offset = $dtsFrom->diffInDays($dtsTo);
            $virtual = config('avito.virtual');

            if ($virtual) {
                $this->addVirtualMachinery($conditions);
            }

            [$machinery, $newDate] = $this->avitoRepository->findMachinery($conditions->avito_ad_id, $conditions->avito_order_id, $dtsFrom, $avitoOrder->rental_duration, $avitoOrder->rental_duration + $offset);

            $conditions->dates->start_date_from = $newDate;

            $customerInfo = $this->getCustomerInfo($conditions->customer->inn);

            if (!$customerInfo && $conditions->customer->inn) {
                throw new CustomerNotFound('Клиент не найден');
            }

            $customer = $this->avitoRepository->addCustomer($conditions, $machinery->company_branch, $customerInfo);

            $avitoOrder->customer_id = $customer->id;

            [$requisites, $documents_pack_id] = $this->avitoRepository->attachDocuments($conditions, $customer, $customerInfo);

            $lead = $this->createLead($customer, $conditions, $documents_pack_id);


            $order = $this->avitoRepository->createOrder(
                $conditions, $documents_pack_id, $customer, $machinery
            );
            $lead->orders()->attach($order->id);
            Audit::query()->where('auditable_type', Order::class)->where('auditable_id', $avitoOrder->order_id)->whereNotNull('new_values->comment')->update([
                'auditable_id' => $order->id
            ]);
            $avitoOrder->company_branch_id = $order->company_branch_id;
            $avitoOrder->order_id = $order->id;

            $this->avitoRepository->attachMachinery($conditions, $order, $machinery, $avitoOrder->rental_duration + $offset);

            $order->getContractUrl();

            $this->avitoRepository->attachContacts($conditions, $customer, $order);

            $order->accept();

            $this->avitoRepository->addPayment($conditions, $order);

            $avitoOrder->save();

            $this->addStats($order->company_branch_id, $avitoOrder);

            OrderChangedEvent::dispatch($order, AvitoOrder::STATUS_CREATED);
            $date = Carbon::parse($order->date_from)->format("d.m.Y H:i");
            $amount = number_format($order->amount / 100, 2, '.', ' ');
            $order = $order->fresh();
            $message = <<<TEXT
            Добрый день!
            
            В TRANSBAZA создан новый заказ с Авито #$order->external_id на $date. 
            Адрес: $order->address.
            Техника: {$order->components->first()?->worker->name}.
            Кол-во смен: {$order->components->first()?->order_duration}
            Стоимость заказа: $amount ₽
            TEXT;

            dispatch(new AvitoNotificaion($order, $message))->delay(Carbon::now()->addSeconds(5));
            DB::commit();
        } catch (GeoServiceUnavailable $e) {
            DB::rollBack();
            $this->updateAvitoLog($e, $log);
            OrderFailedEvent::dispatch($avitoOrder, AvitoOrder::CANCEL_REASON_GEO_NOT_FOUND, $e->getMessage());
        } catch (MachineryNotFound $e) {
            DB::rollBack();
            $this->updateAvitoLog($e, $log);
            OrderFailedEvent::dispatch($avitoOrder, AvitoOrder::CANCEL_REASON_MACHINERY_NOT_FOUND, $e->getMessage());
        } catch (CustomerNotFound $e) {
            DB::rollBack();
            $this->updateAvitoLog($e, $log);
            OrderFailedEvent::dispatch($avitoOrder, AvitoOrder::CANCEL_REASON_CUSTOMER_NOT_FOUND, $e->getMessage());
        } catch (Exception $e) {
            DB::rollBack();
            $this->updateAvitoLog($e, $log);
            $this->logger->error('System failure!');
            $this->logger->error('Message: ' . $e->getMessage());
            $this->logger->error('File: ' . $e->getFile());
            $this->logger->error('Line: ' . $e->getLine());
            $this->logger->error('Trace: ', $e->getTrace());
            $this->logger->error('=============================================================================>');
            OrderFailedEvent::dispatch($avitoOrder, AvitoOrder::CANCEL_REASON_SYSTEM_FAILURE, 'Системная ошибка: ' . $e->getMessage());
            dispatch(new AvitoNotificaion($order,  'Системная ошибка: ' . $e->getMessage()))->delay(Carbon::now()->addSeconds(5));
        }
    }

    private function updateAvitoLog(Exception $e, $log)
    {
        $data = [
            $e->getMessage(),
            $e->getFile(),
            $e->getLine(),
            $e->getTrace(),
        ];
        logger()->error("{$e->getMessage()} {$e->getFile()}{$e->getLine()}", $e->getTrace());
        $log->update([
            'response' => $data,
            'status' => 2,
            'message' => $e->getMessage(),
        ]);
    }

    public function getOrder(GetOrderRequest $request): OrderResource
    {
        $avitoOrder = AvitoOrder::query()->where('avito_order_id', $request->avito_order_id)->latest()->firstOrFail();
        return OrderResource::make($avitoOrder);
    }

    public function cancelOrder(CancelOrderRequest $request): bool
    {
        $avitoOrder = AvitoOrder::query()->where('avito_order_id', $request->avito_order_id)->firstOrFail();
        $order = $avitoOrder->order;

        if (!$order) {
            return false;
        }

        if ($request->return_sum > 0) {

            $avitoOrder->update([
                'return_sum' => $request->return_sum
            ]);

            $amount = number_format($request->return_sum / 100, 2, '.', ' ');
            $subject = "Событие - отмена сделки с источником Avito. Номер сделки: $order->external_id";
            $message = <<<TEXT
            Отменена сделка из Avito.
            Сумма возврата: $amount ₽
            TEXT;

            dispatch(new AvitoNotificaion($order, $message, subject: $subject))->delay(Carbon::now()->addSeconds(5));
        }

        $this->logRequest($avitoOrder, $request->getUri(), $request->validated());

        $service = new OrderService();
        DB::beginTransaction();
        foreach ($order->components->pluck('id') as $positionId) {
            $service->setOrder($order)->rejectApplication($positionId, 'avito_customer');
        }
        DB::commit();
        OrderChangedEvent::dispatch($order, AvitoOrder::STATUS_CANCELED);

        return true;
    }

    public function deleteOrder(AvitoOrder $avitoOrder): bool
    {
        $order = $avitoOrder->order;

        if (!$order || $avitoOrder->status !== 1) {
            return false;
        }

        $this->logRequest($avitoOrder, request()->getUri(), $avitoOrder->toArray());

        $service = new OrderService();
        DB::beginTransaction();
        foreach ($order->components->pluck('id') as $positionId) {
            $service->setOrder($order)->rejectApplication($positionId, 'other');
        }
        DB::commit();
        OrderChangedEvent::dispatch($order, AvitoOrder::STATUS_CANCELED, 4, true);
        return true;
    }

    public function supportRequest(SupportRequest $request)
    {
        $avitoOrder = AvitoOrder::query()->where('avito_order_id', $request->avito_order_id)->firstOrFail();
        $order = $avitoOrder->order;
        $this->logRequest($avitoOrder, $request->getUri(), $request->validated());
        $ssl = config('app.ssl');
        $frontUrl = config('app.front_url');
        $companyBranchId = $order->company_branch->id;
        $companyAlias = $order->company_branch->company->alias;

        $url = "$ssl://$companyAlias.$frontUrl/branch/$companyBranchId/orders/$order->id";
        $notifyMails = config('avito.notify_mails');
        Mail::send([], [], function ($message) use ($order, $url,$notifyMails) {
            $message->to($notifyMails)
                ->subject("Событие – запрос на поддержку с источником Avito. Номер сделки: $order->internal_number")
                ->setBody("<p>Сделан запрос в поддержку для сделки из Avito. Номер сделки: $order->internal_number</p><p>Для просмотра перейдите по ссылке: <a href='$url'>$url</a></p>", 'text/html'); // for HTML rich messages
        });
    }

    private function createLead(
        Customer              $customer,
        CreateOrderConditions $conditions,
        int                   $documentsPackId,
    )
    {
        $companyBranch = $customer->company_branch;
        $employee = $companyBranch->employees()->where('sms_notify', true)->first();
        if(!$employee){
            throw new Exception('Не найден сотрудник для уведомлений. Необходимо установить настройку sms_notify в true в компании' . $companyBranch->company->alias);
        }
        $data = [
            'customer_name' => $customer->name,
            'start_date' => $conditions->dates->start_date_from,
            'title' => $conditions->avito_order_id,
            'phone' => $conditions->customer->phone,
            'email' => $conditions->customer->email,
            'is_fast_order' => false,
            'address' => $conditions->geo->rent_address,
            'object_name' => null,
            'source' => 'avito',
            'status' => Lead::STATUS_ACCEPT,
            'documents_pack_id' => $documentsPackId,
            'creator_id' => $employee->id,
            'company_branch_id' => $companyBranch->id,
            'domain_id' => $this->domain->id,
            'coordinates' => "{$conditions->geo->coordinate_y},{$conditions->geo->coordinate_x}",
        ];
        $lead = new Lead($data);
        $lead->customer()->associate($customer);
        $requisites = $companyBranch->requisite->first();
        $lead->contractorRequisite()->associate($requisites);
        $lead->save();

        $this->createDefaultContract($lead);

        return $lead;
    }

    private function createDefaultContract(Lead $lead): static
    {
        if ($lead->documentsPack && $lead->documentsPack->default_contract_url) {

            $path = config('app.upload_tmp_dir') . "/{$lead->id}_lead_contract.docx";

            $contract = Storage::disk()->get($lead->documentsPack->default_contract_url);
            Storage::disk()->put($path, $contract);

            $lead->addContract(trans('transbaza_order.contract'), $path);
        };

        return $this;
    }

    /**
     * @param CreateOrderConditions $conditions
     * @param string $url
     * @return array
     */
    private function init(CreateOrderConditions $conditions, string $url): array
    {

        $avitoOrder = AvitoOrder::query()->updateOrCreate(
            [
                'avito_ad_id' => $conditions->avito_ad_id,
                'avito_order_id' => $conditions->avito_order_id,
            ],
            [
                'coordinate_x' => $conditions->geo->coordinate_x,
                'coordinate_y' => $conditions->geo->coordinate_y,
                'rent_address' => $conditions->geo->rent_address,
                'start_date_from' => $conditions->dates->start_date_from,
                'start_date_to' => $conditions->dates->start_date_to,
                'rental_duration' => $conditions->dates->rental_duration,
                'customer_name' => $conditions->customer->name,
                'phone' => $conditions->customer->phone,
                'email' => $conditions->customer->email,
                'inn' => $conditions->customer->inn,
                'status' => 1,
                'customer_type' => $conditions->customer->type ?? 1,
                'avito_add_price' => $conditions->avito_add_price
            ]
        );

        $log = $this->logRequest($avitoOrder, $url, $conditions->toArray());

        return array($avitoOrder, $log);
    }

    /**
     * @param AvitoOrder|Model $avitoOrder
     * @param string $url
     * @param array $conditions
     * @return Model
     */
    private function logRequest(AvitoOrder|Model $avitoOrder, string $url, array $conditions): Model
    {
        return AvitoLog::query()->create([
            'avito_order_id' => $avitoOrder->id,
            'request_url' => $url,
            'request_body' => $conditions,
            'response' => '',
            'status' => '',
            'message' => '',
        ]);
    }

    /**
     * @param string|null $inn
     * @return mixed
     * @throws InvalidArgumentException
     */
    private function getCustomerInfo(?string $inn): mixed
    {
        $daData = new DaData();
        if (Cache::has($inn)) {
            $customerInfo = Cache::get($inn);
        } else {
            $customerInfo = $daData->searchByInn($inn, 'LEGAL')->suggestions[0] ?? null;
            Cache::set($inn, $customerInfo);
        }
        return $customerInfo;
    }

    /**
     * @param \Modules\Orders\Entities\Order|Model $order
     * @param mixed $avitoOrder
     * @return void
     */
    private function addStats($companyBranchId, mixed $avitoOrder): void
    {
        AvitoStat::query()->updateOrCreate([
            'company_branch_id' => $companyBranchId
        ], [
            'orders_count' => DB::raw("orders_count + 1")
        ]);

        AvitoOrderHistory::query()->create([
            'avito_order_id' => $avitoOrder->id,
            'company_branch_id' => $companyBranchId
        ]);
    }

    private function addVirtualMachinery(CreateOrderConditions $conditions)
    {
        try {
            $categoryName = 'Спецтехника Авито';
            $alias = Str::replace(' ','-',Str::lower(Str::transliterate($categoryName)));
            $type = Type::query()->updateOrCreate([
                'name' => 'Спецтехника Авито'
            ], [
                'name_style' => $categoryName,
                'alias' => $alias,
                'eng_alias' => "en-" . $alias,
            ]);
            $region = Region::whereName('Москва')->firstOrFail();
            $city = $region->cities()->whereName('Москва')->firstOrFail();
            $brand = Brand::whereName('No Name')->firstOrFail();
            $model = MachineryModel::whereName('No Name')->firstOrFail();

        } catch (\Exception $exception) {
            return response()->json([$exception->getMessage()], 419);
        }
        $companyBranchId = config('avito.virtual_company_id');
        $virtualCompany = CompanyBranch::query()->where('id', $companyBranchId)->firstOrFail();
        $price = $conditions->avito_add_price / 100;
        $machine_data = [
            "contractor_id" => "",
            "id" => "",
            "year" => "",
            "avito_id" => "",
            "avito_ids" => [
                $conditions->avito_ad_id
            ],
            "plans" => [
                "engine_hours" => [
                    "active" => false,
                    "duration" => "01:00",
                    "duration_between_works" => 0,
                    "duration_plan" => 0
                ],
                "days" => [
                    "active" => false,
                    "duration" => "01:00",
                    "duration_between_works" => 0,
                    "duration_plan" => 0
                ],
                "rent_days" => [
                    "active" => false,
                    "duration" => "01:00",
                    "duration_between_works" => 0,
                    "duration_plan" => 0
                ]
            ],
            "rent_with_driver" => false,
            "machine_type" => "machine",
            "vin" => "",
            "brand_id" => $brand->id,
            "category_id" => $type->id,
            "name" => $conditions->avito_ad_title ?: 'Техника № '. $conditions->avito_order_id,
            "tariff_type" => "time_calculation",
            "delivery_radius" => 4000,
            "address" => $conditions->geo->rent_address,
            "region_id" => $region->id,
            "original_equipment_cost" => 0,
            "insurance_premium_cost" => 0,
            "city_id" => $city->id,
            "base_id" => $virtualCompany->machinery_bases->first()->id,
            "has_calendar" => false,
            "default_base_id" => $virtualCompany->machinery_bases->first()->id,
            "model_id" => $model->id,
            "serial_number" => "AV-{$conditions->avito_ad_id}",
            "selling_price" => "",
            "available_for_sale" => false,
            "licence_plate" => Str::random(8),
            "description" => "",
            "market_price" => "",
            "market_price_currency" => "RUB",
            "shift_duration" => 8,
            "min_order" => 1,
            "min_order_type" => "hour",
            "pledge_cost" => 0,
            "free_delivery_distance" => 0,
            "delivery_cost_over" => 0,
            "is_rented" => true,
            "is_rented_in_market" => true,
            "price_includes_fas" => true,
            "is_contractual_delivery" => false,
            "show_market_price" => true,
            "show_company_market_price" => true,
            "contractual_delivery_cost" => "",
            "photo" => [
            ],
            "scans" => [
            ],
            "optional_attributes" => [
            ],
            "currency" => "RUB",
            "wialon_telematic" => [
            ],
            "telematics_type" => "none",
            "prices" => [
                [
                    "is_fixed" => false,
                    "min" => 1,
                    "max" => "",
                    "market_markup" => 0,
                    "unit_compare_id" => 239,
                    "grid_prices" => [
                        "cash" => $price / 8,
                        "cashless_vat" => $price / 8,
                        "cashless_without_vat" => $price / 8
                    ]
                ],
                [
                    "is_fixed" => false,
                    "min" => 1,
                    "max" => "",
                    "market_markup" => 0,
                    "unit_compare_id" => 354,
                    "grid_prices" => [
                        "cash" => $price,
                        "cashless_vat" => $price,
                        "cashless_without_vat" => $price
                    ]
                ]
            ],
            "driver_prices" => [
            ],
            "waypoints_price" => [
                [
                    "distance" => 4000,
                    "cash" => 0,
                    "cashless_vat" => 0,
                    "cashless_without_vat" => 0
                ]
            ],
            "coordinates" => $conditions->geo->coordinate_y . ',' . $conditions->geo->coordinate_x,
            "board_number" => "AV-{$conditions->avito_ad_id}",
        ];

        $service = new VehicleService($virtualCompany);
        try {

            $machine = $service->setData($machine_data)->createVehicle();

        } catch (\Exception $exception) {
            DB::rollBack();
            Log::error($exception->getMessage(), [
                $exception->getTrace()
            ]);
            throw new Exception('Не удалось завести виртуальную технику');
        }
        DB::commit();
        return $machine;
    }
}
