<?php

namespace Modules\RestApi\Entities;

use App\Machines\Type;
use App\Support\Country;
use App\Overrides\Model;
use Modules\ContractorOffice\Entities\Vehicle\Price;

class CategoryAvgMarketPrice extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'price',
        'type',
        'category_id',
        'country_id'
    ];


    function setTypeAttribute($val)
    {
        if(!in_array($val, Price::getTypes())) {
            throw new \InvalidArgumentException('wrong type argument', 500);
        }

        $this->attributes['type'] = $val;
    }


    function country()
    {
        return $this->belongsTo(Country::class);
    }

    function category()
    {
        return $this->belongsTo(Type::class);
    }
}
