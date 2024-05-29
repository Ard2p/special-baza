<?php

namespace App\User;

use App\Overrides\Model;
use OwenIt\Auditing\Contracts\Auditable;
use OwenIt\Auditing\Auditable as Audit;

class Balance extends Model implements Auditable
{
    use Audit;
    public $timestamps = false;
    protected $fillable = [
        'balance', 'user_id', 'type', 'real'
    ];
    protected $appends = ['balance_format'];
    const TYPES = [
        'customer',
        'contractor',
        'widget',
        'tbc',
    ];

    static function type($key)
    {
        return array_search($key, self::TYPES);
    }

    function getBalanceFormatAttribute()
    {
        return humanSumFormat($this->balance);
    }


}
