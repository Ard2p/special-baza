<?php

namespace App\Service;

use App\City;
use App\Machines\Type;
use App\Support\Region;
use App\Overrides\Model;

class Stat extends Model
{
  protected $fillable = [
      'region_id',
      'city_id',
      'category_id',
      'min_cost',
      'max_cost',
      'average',
      'total'
  ];

  function city()
  {
      return $this->belongsTo(City::class);
  }

    function region()
    {
        return $this->belongsTo(Region::class);
    }


    function category()
    {
        return $this->belongsTo(Type::class);
    }
}
