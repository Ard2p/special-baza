<?php

namespace App\Seo;

use App\City;
use App\Support\Region;
use App\Support\SeoServiceDirectory;
use App\Overrides\Model;

class RequestDeleteServicePhone extends Model
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
        $links =  SeoServiceDirectory::where('fields', 'like', "%{$this->phone}%")->get();

        return view('admin.marketing.seo_content.links_services', compact('links'))->render();
    }

}
