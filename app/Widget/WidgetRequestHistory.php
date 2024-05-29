<?php

namespace App\Widget;

use App\Service\Widget;
use App\Overrides\Model;

class WidgetRequestHistory extends Model
{
   protected $fillable = [
       'widget_id', 'widget_key', 'referer', 'success', 'fail'
   ];

   function widget()
   {
       return $this->belongsTo(Widget::class);
   }
}
