<?php

namespace App\Support\AttributesLocales;

use App\Overrides\Model;

class UnitLocale extends Model
{
    protected $fillable = [
        'name',
        'locale',
        'unit_id',
    ];
}
