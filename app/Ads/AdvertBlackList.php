<?php

namespace App\Ads;

use App\Overrides\Model;

class AdvertBlackList extends Model
{
    protected $fillable = [
        'phone',
        'email',
        'advert_id',
    ];


    function advert()
    {
        return $this->belongsTo(Advert::class);
    }
}
