<?php


namespace App\Http\Controllers\Avito\Dto;

use Spatie\DataTransferObject\DataTransferObject;

class CreateOrderCustomerConditions extends DataTransferObject
{
    public ?string $name;
    public string $phone;
    public ?string $email;
    public ?string $inn;
    public ?int $type;
}
