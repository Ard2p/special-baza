<?php

namespace App;

use App\Overrides\Model;

class Rate extends Model
{
    protected $fillable = [
        'from_currency',
        'to_currency',
        'rate',
        'date',
    ];
}
