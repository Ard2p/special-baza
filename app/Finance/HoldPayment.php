<?php

namespace App\Finance;

use App\Service\AlphaBank;
use App\User;
use App\Overrides\Model;

class HoldPayment extends Model
{
    protected $fillable = [
        'user_id',
        'order_id',
        'finance_transaction_id',
        'status',
        'response',
        'proposal_id',
        'request_params'
    ];

    function finance_transaction()
    {
        return $this->belongsTo(FinanceTransaction::class);
    }

    function user()
    {
        return $this->belongsTo(User::class);
    }

    function refuse()
    {
        $this->finance_transaction->refuseFromBank(true);
        $AlphaBank = new AlphaBank();
        $response = $AlphaBank->reversePayment([
            'orderNumber' => urlencode($this->order_id),

        ]);
        if($response['errorCode'] == '0'){
            $this->update([
                'status' => $response['orderStatus'],
            ]);
        }


        return $this;
    }
}
