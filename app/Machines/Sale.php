<?php

namespace App\Machines;

use App\Machinery;
use App\Overrides\Model;

class Sale extends Model
{
   protected $fillable = [
       'machinery_id',
       'price',
       'spot_price',
       'description',
       'moderated'
   ];

   protected $appends = ['moderate_button'];

   function machine()
   {
       return $this->belongsTo(Machinery::class, 'machinery_id');
   }


    function setPriceAttribute($value)
    {
        $this->attributes['price'] = round(str_replace(',', '.', $value) * 100);
    }


    function getPriceFormatAttribute()
    {
        return number_format($this->price / 100, 2, ',', ' ');
    }

    function setSpotPriceAttribute($value)
    {
        $this->attributes['spot_price'] = round(str_replace(',', '.', $value) * 100);
    }

    function getSpotPriceFormatAttribute()
    {
        return number_format($this->spot_price / 100, 2, ',', ' ');
    }


    function getModerateButtonAttribute()
    {
        return view('admin.moderate.button', ['instance' => $this, 'type' => 'sales'])->render();
    }
}
