<?php

namespace App\Http\Controllers\Avito\Services;

use App\Http\Controllers\Avito\Dto\CreateOrderConditions;
use App\Http\Controllers\Avito\Requests\CancelOrderRequest;
use App\Http\Controllers\Avito\Requests\GetOrderRequest;
use App\Http\Controllers\Avito\Requests\SupportRequest;

interface IIntegrationService
{
    public function createOrder(CreateOrderConditions $conditions, string $url): void;

    public function getOrder(GetOrderRequest $request);

    public function cancelOrder(CancelOrderRequest $request);

    public function supportRequest(SupportRequest $request);
}
