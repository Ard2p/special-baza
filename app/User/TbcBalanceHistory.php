<?php

namespace App\User;

use App\User;
use App\Overrides\Model;

class TbcBalanceHistory extends Model
{
    protected $fillable = [
        'user_id', 'admin_id', 'old_sum',
        'new_sum', 'type', 'sum', 'reason', 'email_link_id', 'sms_link_id'
    ];
    protected $appends = [
        'new_sum_format',
        'old_sum_format',
        'sum_format',
        'user_name',
        'type_name',
        'refill',
        'withdrawal',
    ];

    function user()
    {
        return $this->belongsTo(User::class);
    }

    function admin()
    {
        return $this->belongsTo(User::class, 'admin_id');
    }

    function getOldSumFormatAttribute()
    {
        return number_format($this->old_sum / 100, 0, ',', ' ');
    }

    function getNewSumFormatAttribute()
    {
        return number_format($this->new_sum / 100, 0, ',', ' ');
    }

    function getSumFormatAttribute()
    {
        return number_format($this->sum / 100, 0, ',', ' ');
    }

    function getUserNameAttribute()
    {
        return $this->user->id_with_email;
    }

    function getTypeNameAttribute()
    {
        return $this->new_sum > $this->old_sum ? 'Пополнение': 'Списание';
    }

    function getRefillAttribute()
    {
        return $this->new_sum > $this->old_sum ? $this->sum_format: '';
    }

    function getWithdrawalAttribute()
    {
        return $this->new_sum < $this->old_sum ? $this->sum_format: '';
    }
}
