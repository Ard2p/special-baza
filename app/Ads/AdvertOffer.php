<?php

namespace App\Ads;

use App\User;
use App\Overrides\Model;

class AdvertOffer extends Model
{
    protected $fillable = [
        'comment',
        'sum',
        'user_id',
        'advert_id',
        'is_win',
        'feedback',
        'rate',
    ];

    function setSumAttribute($value)
    {
        $this->attributes['sum'] = round(str_replace(',', '.', $value) * 100);
    }

    function getSumFormatAttribute()
    {
        return number_format($this->sum / 100, 0, ',', ' ');
    }

    function advert()
    {
        return $this->belongsTo(Advert::class);
    }

   function scopeUserHasOffer($q, $user_id, $advert_id)
   {
       return $q->whereUserId($user_id)->whereAdvertId($advert_id);
   }

   function user()
   {
       return $this->belongsTo(User::class);
   }
}
