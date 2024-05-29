<?php

namespace App\Finance;

use App\Directories\TransactionType;
use App\Option;
use App\User;
use Carbon\Carbon;
use App\Overrides\Model;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class FinanceTransaction extends Model
{

    use Notifiable;
    protected $fillable = [
        'user_id',
        'type',
        'accept',
        'admin_id',
        'sum',
        'balance_type',
        'requisites_id',
        'requisites_type',
        'status',
        'card_payment',
        'step'
    ];
    protected $appends = ['sum_format', 'description', 'status_name'];
    const TYPE = [
        'in',
        'out'
    ];

    const STATUS = [
        'wait',
        'accept',
        'refuse',
    ];

    const STATUS_LNG = [
        'Ожидание',
        'Подтверждена',
        'Отказ',
    ];

    function getEmailAttribute()
    {
        return Option::findOrFail('transactions_notification_email')->value;
    }

    static function getRequiredFields()
    {
        return [
            'type' => 'required|string|in:out' . ((Auth::user()->isCustomer()) ? ',in' : ''),
            'sum' => 'required|numeric|min:1',
            'transaction_type' => 'required|in:submit,account_payment,card_payment',
        ];
    }

    static function type($key)
    {
        return array_search($key, self::TYPE);
    }

    static function getStatus($key)
    {
        return array_search($key, self::STATUS);
    }

    static function status_lng($key)
    {
        return self::STATUS_LNG[$key];
    }

    public function user()
    {
        return $this->hasOne('App\User', 'id', 'user_id')->withTrashed();
    }

    public function admin()
    {
        return $this->hasOne('App\User', 'id', 'admin_id');
    }

    function scopeWithFilters($q, $request)
    {
        if ($request->filled('sum_from')) {

            $q->where('sum', '>=', (int)$request->input('sum_from') * 100);
        }

        if ($request->filled('sum_to')) {

            $q->where('sum', '<=', (int)$request->input('sum_to') * 100);
        }

        if ($request->filled('date_from')) {

            $q->whereDate('created_at', '>=', Carbon::parse($request->input('date_from')));
        }

        if ($request->filled('date_to')) {

            $q->whereDate('created_at', '<=', Carbon::parse($request->input('date_to')));
        }

        if ($request->filled('status')) {

            $q->where('status', $request->input('status'));
        }

        return $q;
    }

    function scopeCurrentUserAccount($q, $user_id = null, $widget = false)
    {
        $user = (is_null($user_id)) ? Auth::user() : User::findOrFail($user_id);

        return $q->where('balance_type',  $user->cuurent_role);

    }

    function getStatusNameAttribute()
    {
        return self::status_lng($this->status);
    }

    function getSumFormatAttribute()
    {
        return number_format($this->sum / 100, 2, ',', ' ');
    }

    function getDescriptionAttribute()
    {
        return $this->type ? 'Вывод денег со счета' : 'Пополнение счета';
    }


    function scopeUnfinish($q)
    {
        return $q->where('status', self::getStatus('wait'));
    }

    function accept_from_bank($hold = false)
    {
        $curr_status = $hold ? 'hold' : 'refill';
        $transaction = $this;
        $type = FinanceTransaction::TYPE[$transaction->type];
        $account = 'customer';
        if ($type === 'in') {

            $old_sum = $transaction->user->getBalance('customer');
            $transaction->user->incrementCustomerBalance($transaction->sum);

            User\BalanceHistory::create([
                'user_id' => $transaction->user->id,
                'admin_id' => 0,
                'old_sum' => $old_sum,
                'new_sum' => $transaction->user->getBalance($account),
                'type' => User\BalanceHistory::getTypeKey($curr_status),
                /*        'requisite_id' => $transaction->user->getActiveRequisite($account)->id,
                        'requisite_type' => $transaction->user->getActiveRequisiteType($account),*/
                'sum' => $transaction->sum,
                'reason' => TransactionType::getTypeLng($curr_status),
                'billing_type' => $account,
            ]);

            $transaction->user->setRealBalance('customer');

            $transaction->step = 1;
            $transaction->save();


            $clone = (clone $transaction);
            $clone->step = 0;
            $clone->status = FinanceTransaction::getStatus('accept');
            $clone->admin_id = 0;
            FinanceTransaction::create($clone->toArray());

        }
        return true;

    }

    function refuseFromBank()
    {
        $transaction = $this;
        $type = FinanceTransaction::TYPE[$transaction->type];
        $account = 'customer';
        if ($type === 'in') {

            $old_sum = $transaction->user->getBalance('customer');
            $transaction->user->decrementCustomerBalance($transaction->sum);

            User\BalanceHistory::create([
                'user_id' => $transaction->user->id,
                'admin_id' => 0,
                'old_sum' => $old_sum,
                'new_sum' => $transaction->user->getBalance($account),
                'type' => User\BalanceHistory::getTypeKey('refuse_hold'),
                /*        'requisite_id' => $transaction->user->getActiveRequisite($account)->id,
                        'requisite_type' => $transaction->user->getActiveRequisiteType($account),*/
                'sum' => $transaction->sum,
                'reason' => TransactionType::getTypeLng('refuse_hold'),
                'billing_type' => $account,
            ]);

            $clone = (clone $transaction);
            $clone->step = 0;
            $clone->status = FinanceTransaction::getStatus('refuse');
            $clone->admin_id = 0;
            FinanceTransaction::create($clone->toArray());
        }
    }

}
