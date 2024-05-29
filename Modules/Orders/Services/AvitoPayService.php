<?php

namespace Modules\Orders\Services;

use App\Service\AlphaBank;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Modules\Orders\Entities\SystemPayment;
use Ramsey\Uuid\Uuid;

class AvitoPayService
{

    const STATUS_NEW = 0;
    const STATUS_ACCEPTED = 2;


    public function __construct(private AlphaBank $alphaBank)
    {
    }

    public function registerPayment(int $sum, string $description, $successUrl, array $details = [], $failUrl = null, Model $modelOwner = null): array|SystemPayment
    {
        $uuid = (string)Uuid::uuid4();

        $response = $this->alphaBank->setFailUrl($failUrl)
            ->setSuccessUrl($successUrl)
            ->registerPayment([
                'orderNumber' => urlencode($uuid),
                'amount' => $sum,
                'jsonParams' => $details,
                'client_id' => Auth::id(),
                'description' => $description,
                'expirationDate' => Carbon::now()->addHours(24)->toISOString()
            ]);

        if (isset($response['formUrl'])) {

            $payment = new SystemPayment([
                'sum' => $sum,
                'payment_uuid' => $response['orderId'],
                'type' => 'avito',
                'status' => static::STATUS_NEW,
                'details' => $response,
                'user_id' => Auth::id(),
            ]);

            if ($modelOwner) {
                $payment->owner()->associate($modelOwner);
            }
            $payment->save();

            return $payment;
        }

        return $response;
    }

    public function cancelPayment(SystemPayment $systemPayment): bool|array
    {
        $response = $this->alphaBank->reversePayment([
            'orderNumber' => $systemPayment->payment_uuid,
        ]);

        if ($response['errorCode'] == 0) {
            $systemPayment->update([
                'status' => $response['orderStatus']
            ]);
            return true;
        }

        return $response;
    }

    public function getStatus(SystemPayment $systemPayment)
    {
        return $this->alphaBank->getOrderStatus($systemPayment->payment_uuid);
    }
}
