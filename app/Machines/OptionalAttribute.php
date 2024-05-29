<?php

namespace App\Machines;

use App\Directories\Unit;
use App\Machinery;
use App\Option;
use App\Support\AttributesLocales\OptionalAttributeLocale;
use App\Overrides\Model;

class OptionalAttribute extends Model
{
    protected $fillable = [
        'type_id',
        'name',
        'unit_id',
        'field_type',
        'priority',
        'interval',
        'require',
        'min',
        'max',
    ];

    protected $casts = [
        'require' => 'boolean',
        'interval' => 'float',
        'min' => 'float',
        'max' => 'float',
    ];

    protected $appends = [
        'field', 'locales','full_name', 'current_locale_name', 'unit'
    ];
    static $types = [
      'date',
      'text',
      'number',
    ];

    protected $with = ['locale'];

    function locale()
    {
        return $this->hasMany(OptionalAttributeLocale::class);
    }

    function getLocalesAttribute()
    {
        $arr = [];
        foreach (Option::$systemLocales as $locale){
            $opt = $this->locale()->whereLocale($locale)->first();
            $arr[$locale] = $opt ? $opt->name : '';

        }

        return $arr;
    }

    function getCurrentLocaleNameAttribute()
    {
        if(!\App::isLocale('ru')){
            $opt = $this->locale()->whereLocale(\App::getLocale())->first();
            if($opt){
                return $opt->name;
            }
        }
        return $this->name;
    }

    static function type($key)
    {
        return array_search($key, self::$types);
    }

    function getFieldAttribute()
    {
        return self::$types[$this->field_type];
    }

    function unit_directory()
    {
        return $this->belongsTo(Unit::class, 'unit_id');
    }

    function getUnitAttribute()
    {
        return $this->unit_directory->name ?? '';
    }

    function machines()
    {
        return $this->belongsToMany(Machinery::class, 'attribute_machine')->withPivot('value');
    }

    function getFullNameAttribute()
    {
        return $this->pivot ? "{$this->name} - {$this->pivot->value} {$this->unit}" : "{$this->name} {$this->unit}";
    }

    function category()
    {
        return $this->belongsTo(Type::class, 'type_id');
    }


    static function export()
    {
        $data = self::with('category')->get();

        $categories = Type::query()->whereHas('optional_attributes')->get();

        $attributes = clone $data;

        return (new \Rap2hpoutre\FastExcel\FastExcel($attributes))->download('categories.xlsx', function ($attr) use ($categories, $attributes) {

            $arr = [];
            foreach ($categories as $category) {
                $attribute = $attributes->where('type_id', $category->id)->first();

                $arr[$category->name] =  ($attribute ? "{$attribute->full_name}" : '');

                if ($attribute) {
                    $key = $attributes->search(function ($item) use ($attribute) {
                        return $item->id == $attribute->id;
                    });

                    $attributes->pull($key);
                }
            }
            return $arr;
        });
    }



}
