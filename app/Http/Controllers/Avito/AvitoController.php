<?php

namespace App\Http\Controllers\Avito;

use App\Http\Controllers\Avito\Dto\CreateOrderConditions;
use App\Http\Controllers\Avito\Models\AvitoOrder;
use App\Http\Controllers\Avito\Repositories\IIntegrationRepository;
use App\Http\Controllers\Avito\Requests\CancelOrderRequest;
use App\Http\Controllers\Avito\Requests\CreateOrderRequest;
use App\Http\Controllers\Avito\Requests\GetOrderRequest;
use App\Http\Controllers\Avito\Requests\SupportRequest;
use App\Http\Controllers\Avito\Resources\BaseResource;
use App\Http\Controllers\Avito\Services\AvitoService;
use App\Http\Controllers\Avito\Services\IIntegrationService;
use App\Http\Controllers\Controller;
use App\Jobs\CreateAvitoOrderJob;
use App\Service\AlfaBank\AlfaBankInvoiceService;
use App\Service\AlfaBank\Dto\PaymentReceived;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\RestApi\Entities\Domain;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;

class AvitoController extends Controller
{

    private readonly AvitoService $avitoService;

    public function __construct()
    {
        $this->avitoService = new AvitoService();
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
    public function getOrder(GetOrderRequest $request)
    {
        return $this->avitoService->getOrder($request);
    }

    /**
     * @OA\Post (
     *     path="/cancel-order",
     *     summary="Отмена сделки",
     *     description=" Отмена сделки по номеру",
     *     @OA\RequestBody (
     *         @OA\JsonContent(ref="#/components/schemas/CancelOrderRequest"),
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="OK",
     *         @OA\JsonContent(ref="#/components/schemas/BaseResource")
     *     )
     * )
     */
    public function cancelOrder(CancelOrderRequest $request)
    {
        $res = $this->avitoService->cancelOrder($request);
        return BaseResource::make((object)[
            'status' => 1,
            'error_message' => '',
        ]);
    }

    /**
     * @OA\Post (
     *     path="/create-order",
     *     summary="Создание сделки",
     *     description=" Создание сделки",
     *     @OA\RequestBody (
     *         @OA\JsonContent(ref="#/components/schemas/CreateOrderRequest"),
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="OK",
     *         @OA\JsonContent(ref="#/components/schemas/BaseResource")
     *     )
     * )
     */
    public function createOrder(CreateOrderRequest $request)
    {
        $request->validated();
        CreateAvitoOrderJob::dispatch($request->all(), $request->getUri())->onQueue('avito');

        return BaseResource::make((object)[
            'status' => 1,
            'error_message' => '',
        ]);
    }

    /**
     * @OA\Post (
     *     path="/support-request",
     *     summary="Запрос в поддежку",
     *     description="Запрос в поддежку",
     *     @OA\RequestBody (
     *         @OA\JsonContent(ref="#/components/schemas/SupportRequest"),
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="OK",
     *         @OA\JsonContent(ref="#/components/schemas/BaseResource")
     *     )
     * )
     */
    public function supportRequest(SupportRequest $request)
    {
        $this->avitoService->supportRequest($request);
        return BaseResource::make((object)[
            'status' => 1,
            'error_message' => '',
        ]);
    }

    public function deleteOrder(AvitoOrder $avitoOrder)
    {
        $res = $this->avitoService->deleteOrder($avitoOrder);
        return BaseResource::make((object)[
            'status' => 1,
            'error_message' => '',
        ]);
    }


    public function alfaCallback(Request $request)
    {
        $this->logger = new Logger('alfa-bank');
        $this->logger->pushHandler(new StreamHandler(storage_path('logs/alfa-bank/' . now()->format('Y-m-d') . '.log')));
        $this->logger->debug('Incoming payment: ', $request->all());
        $data = $request->all();
        if(count($data) === 1){
            $data = $data[0];
        }

        $data['data']['amount']['amount'] = intval($data['data']['amount']['amount'])*100;
        $data['data']['amountRub']['amount'] = intval($data['data']['amountRub']['amount'])*100;

        $res = [
            "actionType" => $data['actionType'],
            "eventTime" => $data['eventTime'],
            "object" => $data['object'],
            "data" => [
                "amount" => $data['data']['amount'],
                "amountRub" => $data['data']['amountRub'],
                "paymentPurpose" => $data['data']['paymentPurpose'],
                "uuid" => $data['data']['uuid'],
            ]
        ];

        $conditions = new PaymentReceived($res);
        $this->logger->debug('Conditions: ', $conditions->toArray());
        $aS = new AlfaBankInvoiceService();
        $aS->processPayment($conditions);

        return BaseResource::make((object)[
            'status' => 1,
            'error_message' => '',
        ]);
    }

}
