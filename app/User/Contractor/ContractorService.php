<?php

namespace App\User\Contractor;

use App\Article;
use App\City;
use App\Directories\ServiceCategory;
use App\Directories\ServiceOptionalField;
use App\Support\Region;
use App\User;
use App\Overrides\Model;
use Illuminate\Support\Facades\Auth;

class ContractorService extends Model
{
    protected $fillable = [
        'user_id', 'service_category_id', 'region_id',
        'city_id', 'name', 'photo',
        'text', 'size', 'sum', 'alias'
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($item) {
           return $item->generateAlias();
        });

        static::updating(function ($item) {
          return $item->generateAlias();
        });
    }

    function generateAlias()
    {
        $alias =  Article::generateChpu("{$this->name}_{$this->user_id}-{$this->id}");


        $this->alias = $alias;

        return $this;
    }

    function optionalAttributes()
    {
        return $this->belongsToMany(ServiceOptionalField::class, 'attribute_contractor_services');
    }

    function setSumAttribute($value)
    {
        $this->attributes['sum'] = round(str_replace(',', '.', $value) * 100);
    }

    function user()
    {
        return $this->belongsTo(User::class);
    }

    function service()
    {
        return $this->belongsTo(ServiceCategory::class, 'service_category_id');
    }

    function category()
    {
        return $this->belongsTo(ServiceCategory::class, 'service_category_id');
    }

    function region()
    {
        return $this->belongsTo(Region::class);
    }

    function city()
    {
        return $this->belongsTo(City::class);
    }

    function scopeCurrentUser($q)
    {
        return $q->whereUserId(Auth::id());
    }

    function getSumFormatAttribute()
    {
        return $this->sum / 100;
    }

    function getRentUrlAttribute()
    {
        return route('contractor_service_show_rent',  [$this->service->alias, $this->city->alias, $this->region->alias, $this->alias]);
    }
}
