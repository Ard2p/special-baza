<?php

namespace Modules\CorpCustomer\Entities;

use App\Rules\Inn;
use App\Rules\Kpp;
use App\Rules\Ogrn;
use App\User;
use App\Overrides\Model;

class CorpCompany extends Model
{
    protected $fillable = [
        'corp_brand_id',
        'full_name',
        'short_name',
        'address',
        'zip_code',
        'email',
        'phone',
        'inn',
        'kpp',
        'ogrn',
    ];

    static function getRules()
    {
        return [
            'full_name' => 'required|string|min:2|max:255',
            'short_name' => 'required|string|min:2|max:255',
            'address' => 'required|string|min:5|max:1000',
            'zip_code' => 'required|string|min:2|max:255',
            'email' => 'required|email|max:255',
            'phone' => 'required|numeric|digits:11',
            'inn' => new Inn,
            'kpp' => new Kpp,
            'ogrn' => new Ogrn,
        ];
    }

    function brand()
    {
        return $this->belongsTo(CorpBrand::class, 'corp_brand_id');
    }

    function banks()
    {
        return $this->morphToMany(CorpBank::class, 'bank', 'corp_banks_relation');
    }

    function employees()
    {
        return $this->belongsToMany(User::class, 'employees')->withPivot('position');
    }


    function scopeCurrentUser($q)
    {
        return $q->whereHas('brand', function ($q){
            $q->whereUserId(auth()->id());
        });

    }

    function scopeCurrentOrEmployee($q)
    {
        return $q->whereHas('brand', function ($q){
            $q->whereUserId(auth()->id());
        })->orWhereHas('employees', function ($q){
            $q->where('users.id', auth()->id());
        });

    }
    function canEdit()
    {
        return $this->brand->user_id === auth()->id();
    }
}
