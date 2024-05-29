<?php

namespace App\User;

use App\Article;
use App\Overrides\Model;

class SubscribeTemplate extends Model
{
   protected $fillable = [
       'html',
       'subscribe_id',
       'name',
       'is_send',
       'article_id',
       'enable_stats'
   ];


   protected $appends = [
       'send', 'info_url'
   ];
   function subscribe()
   {
       return $this->belongsTo(Subscribe::class);
   }

   function messages()
   {
       return $this->hasMany(SendingSubscribe::class);
   }

   function getSendAttribute()
   {
       return $this->is_send === 1 ? 'Отправлено' : 'Ожидает отправки';
   }

   function getInfoUrlAttribute()
   {
       return route('sending_subscribes_info', $this->id);
   }

   function article()
   {
       return $this->belongsTo(Article::class);
   }
}
