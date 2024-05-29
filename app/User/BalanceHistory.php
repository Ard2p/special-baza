<?php

namespace App\User;

use Carbon\Carbon;
use App\Overrides\Model;
use Illuminate\Http\Request;
use Illuminate\Notifications\Notifiable;

class BalanceHistory extends Model
{
    use Notifiable;
    const TYPES = [
        'refill',
        'withdrawal',
        'reserve',
        'return_reserve',
        'reward',
        'fine',
        'hold',
        'refuse_hold',
    ];

    const TYPES_LNG = [
        'Пополнение',
        'Вывод',
        'Резерв',
        'Возврат резерва',
        'Вознаграждение',
        'Вознаграждение',
    ];

    protected $fillable = [
        'user_id', 'balance_type', 'admin_id', 'old_sum',
        'new_sum', 'type', 'sum', 'reason', 'requisite_id', 'requisite_type', 'billing_type'
    ];

    protected $appends = ['new_sum_format', 'period_start', 'period_end', 'refill', 'withdrawal', 'billing_type_lang'];

    public function user()
    {
        return $this->hasOne('App\User', 'id', 'user_id')->withTrashed();;
    }

    public function admin()
    {
        return $this->hasOne('App\User', 'id', 'admin_id');
    }

    function getNewSumFormatAttribute()
    {
        return number_format($this->new_sum / 100, 2, ',', ' ');
    }

    function getSumFormatAttribute()
    {
        return number_format($this->sum / 100, 2, ',', ' ');
    }

    static function getTypeKey($key)
    {
        return array_search($key, self::TYPES);
    }

    function getPeriodStartAttribute()
    {
        return number_format($this->old_sum / 100, 2, ',', ' ');
    }

    function getPeriodEndAttribute()
    {
        return  $this->new_sum_format;
    }

    function getRefillAttribute()
    {
       return ($this->new_sum > $this->old_sum) ? $this->sum_format : '';
    }

    function getWithdrawalAttribute()
    {
       return ($this->new_sum < $this->old_sum) ? $this->sum_format : '';
    }

    function getBillingTypeLangAttribute()
    {
        return  '';//(request()->filled('__webwidget') ? 'Виджет' : ($this->billing_type === 'customer') ? trans('transbaza_roles.customer'):  trans('transbaza_roles.contractor'));
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

        if ($request->filled('type')) {

            $q->where('type', $request->input('type'));
        }

        return $q;
    }
}
