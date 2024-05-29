<?php


namespace App\Http\Controllers\Avito\Dto;

use Spatie\DataTransferObject\DataTransferObject;

class CreateOrderGeoConditions extends DataTransferObject
{
    public ?string $coordinate_x;
    public ?string $coordinate_y;
    public string $rent_address;
}
