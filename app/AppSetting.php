<?php

namespace App;

use App\Overrides\Model;

class AppSetting extends Model
{
    protected $fillable = [
        'type',
        'data'
    ];

    protected $casts = [
        'data' => 'array'
    ];
}
