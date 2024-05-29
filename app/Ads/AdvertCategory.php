<?php

namespace App\Ads;

use App\Overrides\Model;

class AdvertCategory extends Model
{
  protected $fillable = [
      'name', 'alias'
  ];


  function adverts()
  {
      return $this->hasMany(Advert::class, 'category_id');
  }
}
