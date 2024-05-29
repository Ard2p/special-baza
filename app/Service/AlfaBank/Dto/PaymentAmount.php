<?php


namespace App\Service\AlfaBank\Dto;

use Spatie\DataTransferObject\DataTransferObject;

class PaymentAmount extends DataTransferObject
{

    public int $amount;
    public string $currencyName;
}
