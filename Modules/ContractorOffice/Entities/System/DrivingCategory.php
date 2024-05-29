<?php

namespace Modules\ContractorOffice\Entities\System;

use Illuminate\Database\Eloquent\Model;
use Modules\RestApi\Entities\Domain;

class DrivingCategory extends Model
{

    public $timestamps = false;

    protected $fillable = [
        'domain_id',
        'name',
        'type',
        'description',
    ];

    const TYPE_MACHINERY_LICENCE = 'machinery_licence';
    const TYPE_DRIVING_LICENCE = 'driving_licence';

    function domain()
    {
        return $this->belongsTo(Domain::class);
    }

    function scopeForDomain($q, $domain = null)
    {
        $domain = $domain ?: request()->header('domain');
        return $q->whereHas('domain', function ($q) use ($domain) {
                return is_array($domain)
                    ? $q->whereIn('alias', $domain)
                    : $q->whereAlias($domain);
            });
    }
}
