<?php

namespace App\Support;

use App\Overrides\Model;

class MachineCode extends Model
{
   protected $fillable = [
       'mask',
       'country_id',
       'image',
   ];

   function country()
   {
       return $this->belongsTo(Country::class);
   }
}
