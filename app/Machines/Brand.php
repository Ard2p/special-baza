<?php

namespace App\Machines;

use App\Machinery;
use App\Option;
use App\Support\AttributesLocales\BrandLocale;
use App\Overrides\Model;
use Illuminate\Support\Facades\App;
use Modules\Dispatcher\Entities\Directories\Vehicle;

class Brand extends Model
{
   public $timestamps = false;

   protected $fillable = ['name'];
   protected $with = ['locale'];
    protected $appends = ['locales'];

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

    function locale()
    {
        return $this->hasMany(BrandLocale::class);
    }

    function machines()
    {
        return $this->hasMany(Machinery::class);
    }

    function machineryModels()
    {
        return $this->hasMany(MachineryModel::class);
    }

    function dispatcher_vehicles()
    {
        return $this->hasMany(Vehicle::class);
    }

    function getLocalesAttribute()
    {
        $arr = [];
        foreach (Option::$systemLocales as $locale){
            $opt = $this->locale->where('locale', $locale)->first();
            $arr[$locale] = $opt ? $opt->name : '';

        }

        return $arr;
    }
}
