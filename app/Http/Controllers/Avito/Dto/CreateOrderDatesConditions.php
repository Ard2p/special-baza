<?php


namespace App\Http\Controllers\Avito\Dto;

use Spatie\DataTransferObject\DataTransferObject;

class CreateOrderDatesConditions extends DataTransferObject
{
    public string $start_date_from;
    public string $start_date_to;
    public int $rental_duration;
}
