<?php

namespace App\Directories;

use App\Option;
use App\Support\AttributesLocales\UnitLocale;
use App\Overrides\Model;
use Illuminate\Support\Facades\App;

class Unit extends Model
{

    protected $fillable = ['name'];
    protected $with = ['locale'];
    protected $appends = ['locales'];

    function getNameAttribute($val)
    {
        if (!\App::isLocale('ru')) {
            $loc = $this->locale()->whereLocale('en'/*App::getLocale()*/)->first();
            if ($loc) {
                return $loc->name;
            }
        }
        return $val;
    }

    function locale()
    {
        return $this->hasMany(UnitLocale::class);
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
