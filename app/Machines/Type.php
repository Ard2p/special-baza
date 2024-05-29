<?php

namespace App\Machines;

use App\Helpers\RequestHelper;
use App\Machinery;
use App\Option;
use App\Seo\RequestDeletePhone;
use App\Service\Stat;
use App\Support\AttributesLocales\TypeLocale;
use App\Support\SeoContent;
use App\Overrides\Model;
use Carbon\Carbon;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Storage;
use Modules\ContractorOffice\Entities\Services\CustomService;
use Modules\ContractorOffice\Entities\System\Tariff;
use Modules\Dispatcher\Entities\Directories\Vehicle;
use Modules\RestApi\Entities\CategoryAvgMarketPrice;
use Modules\RestApi\Entities\Content\Tag;

class Type extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'type',
        'name',
        'name_style',
        'eng_alias',
        'alias',
        'licence_plate',
        'rent_with_driver',
        'vin',
        'photo',
        'service_plan_type',
        'amount_between_services',
        'service_duration',
        'amount_days_between_plan_services',
    ];

    protected $casts = ['vin' => 'boolean', 'rent_with_driver' => 'boolean'];

    const TYPE_MACHINE = 'machine';

    const TYPE_EQUIPMENT = 'equipment';

    private $setLocale = false;

    protected $appends = ['directory_link', 'thumbnail_link', 'service_duration_minutes'];

    protected $with = ['locale','avg_prices'];


    static function setLocaleNames($categories) {
        if (\app()->getLocale() !== 'ru') {
            $categories->each->localization();
            return  $categories->sortBy('name')->values()->all();
        }

        return $categories->sortBy('name')->values()->all();
    }

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);

        if (app()->getLocale() !== config('app.fallback_locale')) {
            $this->with[] = 'locale';
        }
    }

    function localization()
    {
        $locale = App::getLocale();

        if (!App::isLocale('ru') && !$this->setLocale) {
            $en = $this->locale->where('locale', $locale)->first();
            $en = $en ?:  $this->locale->where('locale', 'en')->first();
            if ($en) {
                $this->name = $en->name;
                $this->name_style = $en->name;
                $this->setLocale = true;
            }
        }

        return $this;
    }

    function getNameAttribute($val)
    {
        $this->localization();

        return $val;
    }

    function getNameStyleAttribute($val)
    {
        $this->localization();
        return $val;
    }

    function getAliasAttribute($val)
    {
        if (App::getLocale() !== 'ru') {
            return $this->eng_alias;
        }

        return $val;
    }

    function locale()
    {
        return $this->hasMany(TypeLocale::class);
    }

    function getLocalesAttribute()
    {
        $array = Option::$systemLocales;
        $locales = [];

        foreach ($array as $locale) {
            $loc = $this->locale()->where('locale', $locale)->first();
            $locales[$locale] = $loc ? $loc->name : '';
        }

        return $locales;
    }

    function stats()
    {
        return $this->hasMany(Stat::class, 'category_id', 'id');
    }

    function machines()
    {
        return $this->hasMany(Machinery::class, 'type', 'id');
    }

    function machineryModels()
    {
        return $this->hasMany(MachineryModel::class, 'category_id');
    }

    function dispatcher_vehicles()
    {
        return $this->hasMany(Vehicle::class);
    }

    function tags()
    {
        return $this->morphToMany(Tag::class, 'taggable');
    }

    /**
     * Дополнительные тарифы для категории
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    function tariffs()
    {
        return $this->belongsToMany(Tariff::class, 'categories_tariffs');
    }

    function getTariffAttribute()
    {
        return $this->tariffs->first();
    }

    function seo_contents()
    {
        return $this->hasMany(SeoContent::class, 'type_id');
    }


    function getDirectoryLinkAttribute()
    {
        return route('directory_main_category', $this->alias);
    }

    function getThumbnailLinkAttribute()
    {
        $photo = $this->photo ?? 'img/no_product.png';
        return Storage::disk()->url($photo);
    }

    function optional_attributes()
    {
        return $this->hasMany(OptionalAttribute::class);
    }

    function scopeForDomain($q, $domain = 'ru')
    {
        $q->whereHas('machines', function ($q) use ($domain){
            $q->forDomain($domain);
        });

        return $q;
    }

    function avg_prices()
    {
        return $this->hasMany(CategoryAvgMarketPrice::class, 'category_id');
    }

    function getMarketPricesAttribute()
    {
        return $this->avg_prices->where('country_id', RequestHelper::requestDomain()->country->id);
    }

    function services()
    {
        return $this->belongsToMany(CustomService::class, 'custom_services_categories', 'category_id', 'custom_service_id');
    }

    function getServiceDurationAttribute($val)
    {
        return $val ? now()->startOfDay()->addMinutes($val)->format('H:i') : 0;
    }

    function setServiceDurationAttribute($val)
    {
        $this->attributes['service_duration'] = $val ? Carbon::createFromFormat('H:i', $val)->diffInMinutes(now()->startOfDay()) : 0;
    }

    function getServiceDurationMinutesAttribute()
    {
        return  $this->service_duration ? Carbon::createFromFormat('H:i', $this->service_duration)->diffInMinutes(now()->startOfDay()) : 0;
    }
}
