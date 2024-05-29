<?php

namespace Modules\AdminOffice\Entities;

use App\Support\Country;
use Illuminate\Database\Eloquent\Model;

class RpContact extends Model
{
    protected $fillable = [
        'name',
        'email',
        'phone',
        'region',
        'position',
        'country_id',
        'is_supervisor',
        'is_rp',
        'user_id',
    ];

    protected $appends = ['phone_contact', 'data'];

    function getPhoneContactAttribute()
    {
        $phone = $this->phone;
        $mask = $phone
            ? "+{$phone[0]} ({$phone[1]}{$phone[2]}{$phone[3]}) {$phone[4]}{$phone[5]}{$phone[6]}-{$phone[7]}{$phone[8]}-{$phone[9]}{$phone[10]}"
            : '';
        return null;//b64img($mask);
    }


    function getDataAttribute()
    {
        return base64_encode(json_encode(['phone' => $this->phone, 'email' => $this->email]));
    }

    function country()
    {
        return $this->belongsTo(Country::class);
    }

    function scopeCountry($q, $alias)
    {
        return $q->whereHas('country', function ($q) use ($alias) {
            $q->whereAlias($alias);
        });
    }

    function scopeForDomain($q, $domain = null)
    {
        $domain = $domain ?: request()->header('domain');

        if (!$domain) {
            return $q;
        }
        return
            $q->whereHas('country', function ($q) use ($domain) {
                $q->whereHas('domain', function ($q) use ($domain) {
                    $q->whereAlias($domain);
                });
            });
    }
}
