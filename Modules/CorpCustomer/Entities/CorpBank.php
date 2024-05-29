<?php

namespace Modules\CorpCustomer\Entities;

use App\User;
use App\Overrides\Model;

class CorpBank extends Model
{
    protected $fillable = [
        'user_id',
        'name',
        'account',
        'bik',
        'address',
    ];

    function user()
    {
        return $this->belongsTo(User::class);
    }


    function companies()
    {
        return $this->morphedByMany(CorpCompany::class, 'bank', 'corp_banks_relation');
    }

    function brands()
    {
        return $this->morphedByMany(CorpBrand::class, 'bank', 'corp_banks_relation');
    }


    function scopeCurrentUser($q, $user_id = null)
    {

        return $q->whereUserId(($user_id ?: auth()->id()));
    }
}
