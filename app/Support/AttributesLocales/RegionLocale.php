<?php

namespace App\Support\AttributesLocales;

use App\Overrides\Model;

class RegionLocale extends Model
{
    protected $fillable = [
        'name',
        'locale',
        'region_id',
    ];
}
