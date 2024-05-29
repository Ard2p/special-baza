<?php

namespace App\Support\AttributesLocales;

use App\Machines\OptionalAttribute;
use App\Overrides\Model;

class OptionalAttributeLocale extends Model
{
    protected $fillable = [
        'name',
        'locale',
        'optional_attribute_id',
    ];


    function optional_attribute()
    {
        return $this->belongsTo(OptionalAttribute::class);
    }

}
