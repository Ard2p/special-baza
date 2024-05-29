<?php

namespace Modules\CorpCustomer\Entities;

use App\Helpers\RequestHelper;
use App\Rules\Inn;
use App\Rules\Kpp;
use App\Rules\Ogrn;
use App\User;
use App\Overrides\Model;
use Modules\Dispatcher\Entities\DispatcherInvoice;

class CorpBrand extends Model
{
    protected $fillable = [
        'user_id',
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

    static function getRules($id = null)
    {
        return [
            'full_name' => 'required|string|min:2|max:255',
            'short_name' => 'required|string|min:2|max:255',
            'address' => 'required|string|min:5|max:1000',
            'zip_code' => 'required|string|min:2|max:255',
            'email' => 'required|email|max:255',
            'phone' => 'required|numeric|digits:' .  RequestHelper::requestDomain()->options['phone_digits'],
            'inn' => [new Inn, 'unique:corp_brands,inn' . ($id ? ",{$id}" : '')],
            'kpp' => new Kpp,
            'ogrn' => new Ogrn,
        ];
    }

    function main_requisite()
    {
        return $this->morphTo();
    }

    function dispatcherLegalRequisite()
    {
        return $this->morphOne(DispatcherInvoice::class, 'main_requisite' );
    }



    function user()
    {
        return $this->belongsTo(User::class);
    }
    
    function companies()
    {
        return $this->hasMany(CorpCompany::class);
    }

    function scopeCurrentUser($q)
    {
        return $q->whereUserId(auth()->id());
    }

    function banks()
    {
        return $this->morphToMany(CorpBank::class, 'corp_bank', 'corp_banks_relation');
    }
}
