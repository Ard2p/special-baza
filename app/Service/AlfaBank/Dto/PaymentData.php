<?php


namespace App\Service\AlfaBank\Dto;

use Spatie\DataTransferObject\DataTransferObject;

class PaymentData extends DataTransferObject
{
    public PaymentAmount $amount;
    public PaymentAmount $amountRub;
    public string $paymentPurpose;
    public string $uuid;
}
