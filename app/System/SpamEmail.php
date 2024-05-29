<?php

namespace App\System;

use App\User;
use App\Overrides\Model;

class SpamEmail extends Model
{
   protected $fillable = ['email', 'spam_system'];

   protected $appends = ['status', 'block_url', 'blocked', 'unblock_url'];

   function user()
   {
       return $this->belongsTo(User::class, 'email', 'email');
   }

   function getStatusAttribute()
   {
      return $this->user
          ? $this->user->is_blocked
              ? 'Заблокирован'
              : 'Активен'
          : 'Удален из системы';
   }

    function getBlockedAttribute()
    {
        return $this->user
            ? $this->user->is_blocked
                ? true
                : false
            : 0;
    }

   function getBlockUrlAttribute()
   {
       return route('block_spam_user', ($this->user ? $this->user->id : 0));
   }

    function getUnblockUrlAttribute()
    {
        return route('un_block_spam_user', ($this->user ? $this->user->id : 0));
    }
}
