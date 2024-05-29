<?php

namespace Modules\AdminOffice\Entities;

use App\Support\Country;
use Illuminate\Database\Eloquent\Model;
use Spatie\EloquentSortable\Sortable;
use Spatie\EloquentSortable\SortableTrait;

class SiteFeedback extends Model implements Sortable
{
    use SortableTrait;

    protected $table = 'site_feedback';

    public $sortable = [
        'order_column_name' => 'order_column',
        'sort_when_creating' => true,
    ];

    protected $fillable = [
        'name',
        'content',
        'rate',
        'country_id',
    ];


    function country()
    {
        return $this->belongsTo(Country::class);
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

    function scopeCountry($q, $alias)
    {
        return $q->whereHas('country', function ($q) use ($alias){
            $q->whereAlias($alias);
        });
    }
}
