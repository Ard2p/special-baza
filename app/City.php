<?php

namespace App;

use App\Ads\Advert;
use App\Directories\CityCode;
use App\Service\Stat;
use App\Support\AttributesLocales\CityLocale;
use App\Support\Region;
use App\Support\SeoContent;
use App\Support\SeoServiceDirectory;
use App\User\Contractor\ContractorService;
use App\Overrides\Model;
use Illuminate\Support\Facades\App;
use Modules\Dispatcher\Entities\Directories\Contractor;
use Modules\Dispatcher\Entities\Directories\Vehicle;
use Rap2hpoutre\FastExcel\FastExcel;

class City extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'name', 'region_id', 'alias', 'coordinates', 'is_capital'
    ];

    protected $casts = [
        'is_capital' => 'boolean'
    ];

   // protected $appends = ['with_codes'];

   // protected $with = ['phone_codes'];

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);

        if (app()->getLocale() !== config('app.fallback_locale')) {
            $this->with[] = 'locale';
        }
    }

    public function newQuery($excludeDeleted = true)
    {
        $raw = ' ST_AsText(coordinates) as coordinates ';

        return parent::newQuery($excludeDeleted)->addSelect('*', \DB::raw($raw));
    }

    function locale()
    {
        return $this->hasMany(CityLocale::class);
    }

    function getNameAttribute($val)
    {
        $name = $val;
        if (!App::isLocale('ru')) {
            $loc = $this->locale->where('locale', App::getLocale())->first();
            if ($loc) {
                $name = $loc->name;
            }
        }

        return $name;
    }

    public function region()
    {
        return $this->hasOne(Region::class, 'id', 'region_id');
    }

    function phone_codes()
    {
        return $this->hasMany(CityCode::class, 'city_id', 'id');
    }

    function machines()
    {
        return $this->hasMany(Machinery::class);
    }

    function dispatcher_contractors()
    {
        return $this->hasMany(Contractor::class);
    }

    function contractor_services()
    {
        return $this->hasMany(ContractorService::class);
    }

    function adverts()
    {
        return $this->hasMany(Advert::class);
    }

    function users()
    {
        return $this->hasMany(User::class, 'native_city_id');
    }


    function stats()
    {
        return $this->hasMany(Stat::class, 'city_id', 'id');
    }

    function seo_content()
    {
        return $this->hasMany(SeoContent::class);
    }

    function seo_service_content()
    {
        return $this->hasMany(SeoServiceDirectory::class);
    }

    function scopeWhereRegion($q, $id)
    {
        return $q->where('region_id', $id);
    }

    public function getCodesAttribute()
    {
        $codes = '';

        foreach ($this->phone_codes as $code) {
            $codes .= $code->code . ' ';
        }

        return "{$codes}";
    }

    public function getWithCodesAttribute()
    {
        $codes = '';

        foreach ($this->phone_codes as $code) {
            $codes .= $code->code . ' ';
        }

        return "{$this->name} {$codes}";
    }

    function scopeOnlyName($q)
    {
        return $q->select(['id', 'name', 'region_id']);
    }

    function setCoordinatesAttribute($val)
    {
        if(!$val) {
            return $val;
        }
        $coords = explode(',', trim($val));
        $query = "GeomFromText('POINT($coords[0] $coords[1])')";
        $this->attributes['coordinates'] = \DB::raw($query);

    }


    function scopeForDomain($q, $domain = null)
    {
        $domain = $domain ?: request()->header('domain');

        if (!$domain) {
            return $q;
        }
        return $q->whereHas('region', function ($q) use ($domain) {

            $q->whereHas('country', function ($q) use ($domain) {

                $q->whereHas('domain', function ($q) use ($domain) {

                   return is_array($domain)
                        ? $q->whereIn('alias', $domain)
                        : $q->whereAlias($domain);
                });

            });
        });

    }


    function getCoordinatesAttribute($value)
    {
        $response = explode(
            ' ',
            str_replace(
                [
                    "GeomFromText('",
                    "'",
                    'POINT(',
                    ')'
                ],
                '',
                $value
            )
        );

        if (isset($response[1])) {
            return [
                'lat' => (float) $response[0],
                'lng' => (float) $response[1]
            ];
        }
        return null;
    }

    static function export()
    {
        $collection = self::query()->whereHas('region', function ($q) {
            $q->where('name', 'like', '%Самар%');
        })->get();

       return (new FastExcel($collection))->download('Example.xlsx', function ($coll) {

            return [
                'Наименование' => $coll->name,
            ];
        });
    }
}
