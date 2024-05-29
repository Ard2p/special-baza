<?php

namespace App\Support\AttributesLocales;

use Illuminate\Database\Eloquent\Model;

class CityLocale extends Model
{
    protected $fillable = [
        'name',
        'locale',
        'city_id',
    ];
}
