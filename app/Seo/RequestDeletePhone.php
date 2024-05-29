<?php

namespace App\Seo;

use App\City;
use App\Support\Region;
use App\Support\SeoContent;
use App\Overrides\Model;

class RequestDeletePhone extends Model
{
    protected $fillable = [
        'phone', 'name', 'url',
        'region_id', 'city_id',
        'comment'
    ];


    function city()
    {
        return $this->belongsTo(City::class);
    }

    function region()
    {
        return $this->belongsTo(Region::class);
    }

    function getFoundedLinksAttribute()
    {
       $links =  SeoContent::where('fields', 'like', "%{$this->phone}%")->get();

       return view('admin.marketing.seo_content.links', compact('links'))->render();
    }

}
