<?php

namespace App;

use App\Overrides\Model;

class SystemCashHistory extends Model
{

    protected $fillable = [
        'old_sum', 'new_sum',
        'sum', 'type', 'reason',
    ];

    static function getCurrentCash()
    {
        return Option::findOrFail('system_cash')->value;
    }

    static function incrementCash($sum)
    {
        $cash = Option::findOrFail('system_cash');

        $result = (int)$cash->value + $sum;

        $cash->update([
            'value' => $result
        ]);

        return $result;
    }

    static function decrementCash($sum)
    {

        $cash = Option::findOrFail('system_cash');

        $result = (int)$cash->value - $sum;

        $cash->update([
            'value' => $result
        ]);

        return $result;
    }
}
