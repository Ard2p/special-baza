<?php

namespace App\Support;

use App\Overrides\Model;

class FederalDistrict extends Model
{
    protected $fillable = ['name'];


    function scopeForDomain($q, $domain = null)
    {
        $domain = $domain ?: request()->header('domain');

        if (!$domain) {
            return $q;
        }
        return $q->whereHas('country', function ($q) use ($domain) {
                $q->whereHas('domain', function ($q) use ($domain) {
                    $q->whereAlias($domain);
                });
            });
    }

    function country()
    {
        return $this->belongsTo(Country::class);
    }
}
