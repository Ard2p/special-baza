<?php


namespace App\Http\Controllers\Avito\Dto;

use Spatie\DataTransferObject\DataTransferObject;

class CreateOrderIndividualConditions extends DataTransferObject
{
    public string $name;
    public string $birth_date;
    public string $passport_number;
    public string $passport_date;
    public string $issued_by;
    public string $kp;
    public string $register_address;
}
