<?php

namespace App\User;

use App\Directories\NotificationName;
use App\Overrides\Model;

class NotificationHistory extends Model
{
   protected $fillable = [
       'user_id',
       'notification_name_id',
       'type',
   ];
   protected $appends = ['name'];

   function notification_name()
   {
       return $this->belongsTo(NotificationName::class);
   }

   function getNameAttribute()
   {
       return $this->notification_name->name ?? '';
   }


}
