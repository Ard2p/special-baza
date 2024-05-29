<?php

namespace App\Support;

use App\Ads\Advert;
use App\City;
use App\Directories\GibddCode;
use App\Helpers\RequestHelper;
use App\Machinery;
use App\Service\RequestBranch;
use App\Service\Stat;
use App\Support\AttributesLocales\RegionLocale;
use App\User;
use App\User\Contractor\ContractorService;
use App\Overrides\Model;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;

class Region extends Model
{

    protected $fillable = ['name', 'number', 'style_name', 'country_id', 'alias', 'federal_district_id'];

    protected $appends = ['full_name'];

    protected $with = ['locale'];

    public $timestamps = false;


    function locale()
    {
        return $this->hasMany(RegionLocale::class);
    }

    function country()
    {
        return $this->belongsTo(Country::class);
    }

    public function cities()
    {
        return $this->hasMany(City::class, 'region_id', 'id')->orderBy('name');
    }

    function federal_district()
    {
        return $this->belongsTo(FederalDistrict::class);
    }

    function gibdd_codes()
    {
        return $this->hasMany(GibddCode::class, 'region_id', 'id');
    }

    function stats()
    {
        return $this->hasMany(Stat::class, 'region_id', 'id');
    }

    public function machines()
    {
        return $this->hasMany(Machinery::class, 'region_id', 'id');
    }

    public function users()
    {
        return $this->hasMany(User::class, 'native_region_id');
    }

    public function contractor_services()
    {
        return $this->hasMany(ContractorService::class, 'region_id', 'id');
    }

    public function adverts()
    {
        return $this->hasMany(Advert::class);
    }

    function getNameAttribute($val)
    {
        if (!App::isLocale('ru')) {
            $loc = $this->locale->where('locale', App::getLocale())->first();
            if ($loc) {
                return $loc->name;
            }
        }
        return $val;
    }

    public function getFullNameAttribute()
    {
        $name = $this->name;
        if (!App::isLocale('ru')) {
            $loc = $this->locale->where('locale', App::getLocale())->first();
            if ($loc) {
                $name = $loc->name;
            }
        }
       /* $codes = $this->gibdd_codes()->pluck('code')->toArray();
        foreach ($codes as $key => $value) {
            if ($value == $this->number) {
                unset($codes[$key]);
                continue;
            }
        }
        $gibdd = implode(', ', $codes);*/
        return $name;
    }

    public function setFullNameAttribute()
    {

        $this->attributes['full_name'] = "{$this->number} - {$this->name}";
    }

    function scopeForDomain($q, $domain = null)
    {
        $domain = $domain ?: request()->header('domain');
        $company = app(RequestBranch::class)->company;
        if($company) {
            $domain = $company->domain->alias ?: $domain;
        }
        if (!$domain) {
            return $q;
        }
        return $q->whereHas('country', function ($q) use ($domain) {
            $q->whereHas('domain', function ($q) use ($domain) {
                $q->whereAlias($domain);
            });
        });

    }

    function scopeWhereCountry($q, $alias)
    {
        return $q->whereHas('country', function ($q) use ($alias) {
            $q->whereAlias($alias);
        });
    }

    function scopeLocalization($q)
    {

        return Auth::check()
            ? $q->whereCountryId(Auth::user()->country_id)
            : $q->whereIn('country_id', RequestHelper::requestDomain()->countries->pluck('id')->toArray());
    }


}
