<?php

namespace App\Machines;

use App\City;
use App\Support\Region;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class SearchFilter extends Model
{

    protected $fillable = ['name', 'fields', 'user_id'];

    protected $appends = ['type_name', 'region_name', 'brand_name', 'city_name'];

    function getNameAttribute($value)
    {
        return mb_strlen ($value)  >  20 ? mb_substr($value, 0, 20) . '...' : $value;
    }

    function scopeCurrentUser($query)
    {
        return $query->where('user_id', Auth::user()->id);
    }

    function getFieldsAttribute($value)
    {
        return json_decode($value);
    }

    function getRegionNameAttribute()
    {

        $region = Region::find($this->fields->region ?? '');

        return $region->full_name ?? '';
    }

    function getTypeNameAttribute()
    {
        $type = Type::find($this->fields->type ?? '');
        return $type->name ?? '';
    }

    function getBrandNameAttribute()
    {
        $brand = Brand::find($this->fields->brand ?? '');
        return $brand->name ?? '';
    }

    function getCityNameAttribute()
    {
        $city = City::find($this->fields->city_id ?? '');
        return $city->with_codes ?? '';
    }
}
