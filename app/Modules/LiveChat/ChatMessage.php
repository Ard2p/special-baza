<?php

namespace App\Modules\LiveChat;

use App\User;
use App\Overrides\Model;

class ChatMessage extends Model
{

    protected $fillable = ['message', 'ip', 'user_id', 'chat_id'];

    protected $appends = ['avatar', 'user_profile_url'];
    function user()
    {
        return $this->belongsTo(User::class);
    }

    function chat()
    {
        return $this->belongsTo(Chat::class);
    }

    function getAvatarAttribute()
    {
        return $this->user ? $this->user->avatar : '/img/no_product.png';
    }

    function getUserProfileUrlAttribute()
    {
        return route('user_public_page', $this->user->contractor_alias ?? 0);
    }



}
