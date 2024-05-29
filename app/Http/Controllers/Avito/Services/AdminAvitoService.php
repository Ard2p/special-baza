<?php

namespace App\Http\Controllers\Avito\Services;

use App\Http\Controllers\Avito\Repositories\AdminAvitoRepository;
use App\Http\Controllers\Avito\Requests\AdminGetOrdersRequest;
use App\Http\Controllers\Avito\Resources\AdminOrderResource;
use Modules\RestApi\Entities\Domain;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;

class AdminAvitoService
{

    private readonly AdminAvitoRepository $avitoRepository;
    private readonly Domain $domain;

    public function __construct()
    {
        $this->domain = Domain::whereAlias('ru')->first();
        $this->avitoRepository = new AdminAvitoRepository($this->domain);

    }

    public function getOrders(AdminGetOrdersRequest $request)
    {
        $avitoOrders = $this->avitoRepository->getOrders($request);

        return AdminOrderResource::collection($avitoOrders);
    }

    public function getOrdersResults(AdminGetOrdersRequest $request)
    {
        $avitoOrdersResults = $this->avitoRepository->getOrdersResults($request);

        $amount_result = 0;
        $avito_dotation_result = 0;
        foreach($avitoOrdersResults as $avitoOrderResults){
            if($avitoOrderResults->order){
                $amount_result += $avitoOrderResults->order->amount;
                $avito_dotation_result += $avitoOrderResults->order->workers->sum('avito_dotation_sum');
            }
        }

        return [
            'amount_result' => $amount_result,
            'avito_dotation_result' => $avito_dotation_result,
        ];
    }
}