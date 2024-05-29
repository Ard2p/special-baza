<?php

namespace App\Modules\LiveChat;

use App\Overrides\Model;

class Chat extends Model
{

    protected $with = ['messages'];
   function messages()
   {
       return $this->hasMany(ChatMessage::class);
   }
}
