<?php


namespace App\Http\Controllers\Avito\Dto;

use Spatie\DataTransferObject\DataTransferObject;

class CreateOrderConditions extends DataTransferObject
{
    public string $token;
    public string $avito_order_id;
    public int $avito_ad_id;
    public ?int $avito_add_price;
    public ?string $from;
    public ?string $avito_ad_title;

    public ?string $comment;
    public CreateOrderGeoConditions $geo;
    public CreateOrderDatesConditions $dates;
    public CreateOrderCustomerConditions $customer;
}
