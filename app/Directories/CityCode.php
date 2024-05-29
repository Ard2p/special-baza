<?php

namespace App\Directories;

use App\City;
use App\Overrides\Model;

class CityCode extends Model
{


   public $timestamps = false;

   protected $fillable = ['city_id', 'code'];

   function city()
   {
       return $this->hasOne(City::class, 'id', 'city_id');
   }
}
