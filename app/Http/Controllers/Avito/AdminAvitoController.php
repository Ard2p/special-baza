<?php

namespace App\Http\Controllers\Avito;

use App\Http\Controllers\Avito\Dto\CreateOrderConditions;
use App\Http\Controllers\Avito\Models\AvitoOrder;
use App\Http\Controllers\Avito\Repositories\IIntegrationRepository;
use App\Http\Controllers\Avito\Requests\AdminGetOrdersRequest;
use App\Http\Controllers\Avito\Requests\CancelOrderRequest;
use App\Http\Controllers\Avito\Requests\CreateOrderRequest;
use App\Http\Controllers\Avito\Requests\GetOrderRequest;
use App\Http\Controllers\Avito\Requests\SupportRequest;
use App\Http\Controllers\Avito\Resources\BaseResource;
use App\Http\Controllers\Avito\Services\AdminAvitoService;
use App\Http\Controllers\Avito\Services\AvitoService;
use App\Http\Controllers\Avito\Services\IIntegrationService;
use App\Http\Controllers\Controller;
use App\Jobs\CreateAvitoOrderJob;
use App\Service\AlfaBank\AlfaBankInvoiceService;
use App\Service\AlfaBank\Dto\PaymentReceived;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Orders\Entities\Order;
use Modules\RestApi\Entities\Domain;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;

class AdminAvitoController extends Controller
{

    private readonly AdminAvitoService $avitoService;

    public function __construct()
    {
        $this->avitoService = new AdminAvitoService();
    }

    /**
     * @OA\Get (
     *     path="/get-order",
     *     summary="Получение информации о сделке",
     *     description="Получение информации о сделке по номеру",
     *
     *     @OA\Parameter(name="token", in="query", description="Токен авторизации пользователя", required=true, @OA\Schema(type="string")),
     *     @OA\Parameter( name="avito_order_id", in="query", description="Номер сделки в Avito", required=true, @OA\Schema(type="string")),
     *
     *     @OA\Response(
     *         response=200,
     *         description="OK",
     *         @OA\JsonContent(ref="#/components/schemas/OrderResource")
     *     )
     * )
     */
    public function getOrders(AdminGetOrdersRequest $request)
    {
        return $this->avitoService->getOrders($request);
    }

    public function getAvitoOrdersResults(AdminGetOrdersRequest $request)
    {
        return $this->avitoService->getOrdersResults($request);
    }

    public function getAvitoOrdersReference()
    {
        return [
            'order_status' => Order::PROP_STATUS_LNG,
            'avito_order_status' => [
                AvitoOrder::STATUS_CREATED => 'created',
                AvitoOrder::STATUS_CANCELED => 'canceled',
                AvitoOrder::STATUS_PROPOSED => 'proposed',
                AvitoOrder::STATUS_PREPAID => 'prepaid',
                AvitoOrder::STATUS_FINISHED => 'finished'
            ],
            'avito_order_cancel_reason' => [
                AvitoOrder::CANCEL_REASON_MACHINERY_NOT_FOUND => 'Техника не найдена',
                AvitoOrder::CANCEL_REASON_CUSTOMER_NOT_FOUND => 'Клиент не найден',
                AvitoOrder::CANCEL_REASON_GEO_NOT_FOUND => 'Адрес не найден',
                AvitoOrder::CANCEL_REASON_SYSTEM_FAILURE => 'Системная ошибка',
            ],
        ];
    }
}
