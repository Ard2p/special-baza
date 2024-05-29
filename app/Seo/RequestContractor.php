<?php

namespace App\Seo;

use App\City;
use App\Machines\Type;
use App\Overrides\Model;

class RequestContractor extends Model
{
    protected $fillable = [
        'email', 'city_id', 'type_id', 'phone', 'name', 'comment'
    ];

    function city()
    {
        return $this->belongsTo(City::class);
    }

    function type()
    {
        return $this->belongsTo(Type::class);
    }
}
