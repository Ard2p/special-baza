<?php

namespace App\Support\AttributesLocales;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\App;

class BrandLocale extends Model
{
   protected $fillable = [
       'name',
       'locale',
       'brand_id',
   ];



}
