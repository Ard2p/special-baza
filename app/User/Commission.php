<?php

namespace App\User;

use App\Overrides\Model;

class Commission extends Model
{
    public $timestamps = false;

    protected $fillable = ['user_id',
        'enable',
        'percent',
    ];

    function getPercentFormatAttribute()
    {
        return $this->percent / 100;
    }

}
