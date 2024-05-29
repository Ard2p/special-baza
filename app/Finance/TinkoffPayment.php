<?php

namespace App\Finance;

use App\Machinery;
use App\Machines\FreeDay;
use App\User;
use Carbon\Carbon;
use GuzzleHttp\Client;
use GuzzleHttp\RequestOptions;
use App\Overrides\Model;
use Modules\Orders\Entities\Order;

class TinkoffPayment extends Model
{

    protected $fillable = [
        'payment_id', 'status', 'amount', 'url', 'interior_payment_id'
    ];

    const TYPES = [
        'tinkoff',
        'tinkoff_partial'
    ];

    protected $hidden = ['interior_payment_id'];

    protected $appends = ['sum_format', 'status_lang'];

    function payment()
    {
        return $this->belongsTo(\Modules\Orders\Entities\Payment::class);
    }


    function generateData($items, $partial = false, $percent = 20)
    {

        /* "code": "251856",
     "shopCode": 251856,
        transbaza 700000038
        c-cars 700000039
        */
        $vehicles = collect($items['items']);
        $shops = collect($items['shops']);
        if($partial) {

            $vehicles = $vehicles->map(function ($vehicle) use ($percent){

               $vehicle['Amount'] = round($vehicle['Amount'] * $percent / 100);
               return $vehicle;
           });

            $shops = $shops->map(function ($shop) use ($percent){

                $shop['Amount'] = round($shop['Amount'] * $percent / 100);
                return $shop;
            });

        }
        $data = [
            'Amount' => $partial ? round($this->amount * $percent / 100) : $this->amount,
            'OrderId' => $this->id,
            'RedirectDueDate' => now()->addMinutes(15)->format('c'),
            'Description' => "Заказ техники" . $partial ? '(Предоплата)' : '',
            'SuccessURL' => origin('payment-success', ['id' => $this->payment_id], config('request_domain')),
            'Receipt' => [
                'Email' => $this->payment->user->email,
                'Taxation' => 'usn_income_outcome',
                'Items' => $vehicles->toArray()
            ],
            'Shops' => $shops->toArray()
        ];


        return $data;
    }

    function updateState()
    {
        $tinkoff = new TinkoffMerchantAPI();
        $status = $tinkoff->getState([
            'PaymentId' => $this->interior_payment_id
        ])->status;
        if($status) {
          $this->update(['status' => $status]);
        }
        return $this;
    }

    function hasHolds()
    {
        return $this->payment->order->holds->isNotEmpty();
    }

    function scopeHasHold($q)
    {
        return $q->whereHas('payment', function ($q){
            $q->hasHold();
        });
    }

    function updateData(TinkoffMerchantAPI $response)
    {
        $this->update([
            'status' => $response->status,
            'interior_payment_id' => $response->paymentId,
            'url' => $response->paymentUrl,
        ]);
        return $this;
    }

    function getSumFormatAttribute()
    {
        return humanSumFormat($this->amount);
    }


    function getStatusLangAttribute()
    {

        return $this->status ? TinkoffMerchantAPI::STATUS_LANG[$this->status] : '';
    }


    function reverse(): void
    {
        $tinkoff = new TinkoffMerchantAPI();

        $tinkoff->cancel([
            'PaymentId' => $this->interior_payment_id
        ]);

        $this->updateState();
    }

    function cancel()
    {
        $tinkoff = new TinkoffMerchantAPI();
        $tinkoff->cancel([
            'PaymentId' => $this->interior_payment_id
        ]);

    }

}
