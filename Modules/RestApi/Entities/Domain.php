<?php

namespace Modules\RestApi\Entities;

use App\Support\Country;
use App\Overrides\Model;
use Modules\AdminOffice\Entities\Marketing\Mailing\Template;

class Domain extends Model
{

    public $timestamps  = false;
    protected $fillable = [
        'name',
        'alias',
        'url',
        'options',
    ];

    protected $casts = [
        'options' => 'array'
    ];

    protected $with = ['currencies', 'countries'];

    protected $appends = ['vat'];


    function countries()
    {
        return $this->hasMany(Country::class, 'domain_id');
    }

    function getVatAttribute()
    {
        $country = $this->countries->first();

        return $country ? $country->vat : 20;
    }

    function getUrlAttribute($val)
    {

     return $this->alias === 'ru' && config('app.env') === 'production'
         ? $val
         : "{$val}/{$this->alias}-{$this->options['default_locale']}";

    }

    function getPureUrlAttribute()
    {
        return $this->attributes['url'];
    }


    function currencies()
    {
        return $this->hasMany(Currency::class);
    }


    function getCurrencyAttribute()
    {
        return $this->currencies->first();
    }


    function getCountryAttribute()
    {
        return $this->countries->first();
    }

    function mailing_templates()
    {
        return $this->hasMany(Template::class);
    }


}
