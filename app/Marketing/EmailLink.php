<?php

namespace App\Marketing;

use App\Ads\Advert;
use App\User;
use Illuminate\Database\Eloquent\Model;

class EmailLink extends Model
{
    protected $fillable = [
        'friends_list_id', 'link', 'machine_id',
        'confirm_status', 'is_watch', 'watch_at',
        'confirm_at',   'hash', 'custom'
    ];

    function friend()
    {
        return $this->belongsTo(FriendsList::class, 'friends_list_id')->withTrashed();
    }

    function advert()
    {
        return $this->belongsToMany(Advert::class, 'advert_send_email')->withPivot('user_id');
    }

    function getUserAttribute()
    {
        return User::whereEmail($this->friend->email)->first();
    }

    function getAdvertAttribute()
    {
        return $this->advert()->first();
    }

    function getAdvertUnsubscribeAttribute()
    {
        if($this->advert) {
            return route('unsubscribe_advert',
                [
                    'alias' => $this->advert->alias,
                    'id' => $this->id,
                    'type' => 'email',
            ]);
        }
    }

    function scopeForAdvert($q, $id){
        return $q->whereHas('advert', function ($q) use($id){
            $q->where('adverts.id', $id);
        });
    }
}
