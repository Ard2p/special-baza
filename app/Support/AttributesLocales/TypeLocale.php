<?php

namespace App\Support\AttributesLocales;

use App\Overrides\Model;

class TypeLocale extends Model
{
    protected $fillable = [
        'name',
        'locale',
        'type_id',
    ];
}
