<?php

namespace App\Directories;

use Illuminate\Database\Eloquent\Model;

class ServiceOptionalField extends Model
{

   protected $fillable = [
       'service_category_id', 'name',
       'unit_id', 'field_type',
   ];

   function unit()
   {
       return $this->belongsTo(Unit::class);
   }

   function serviceCategory()
   {
       return $this->belongsTo(ServiceCategory::class);
   }


}
