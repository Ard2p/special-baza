<?php


namespace App\Http\Controllers\Avito\Dto;

use Spatie\DataTransferObject\DataTransferObject;

class CreateOrderLegalConditions extends DataTransferObject
{
    public ?string $name;
    public string $inn;
}
