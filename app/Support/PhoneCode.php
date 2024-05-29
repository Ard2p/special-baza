<?php

namespace App\Support;

use App\Overrides\Model;

class PhoneCode extends Model
{

    protected $fillable = ['mask', 'country_id'];

    function country()
    {
        return $this->belongsTo(Country::class);
    }
}
