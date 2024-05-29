<?php

namespace App\User;

use App\Role;
use App\User;
use App\Overrides\Model;
use Illuminate\Support\Facades\Auth;

class Subscribe extends Model
{
    protected $fillable = [
        'name',
        'alias',
        'is_system',
        'is_send',
        'can_unsubscribe'
    ];

    protected $appends = [
        'status',
        'is_subscribe',
        'update_link',
    ];


    function unsubscribes()
    {
        return $this->belongsToMany(User::class, 'unsubscribe_user');
    }

    function roles()
    {
        return $this->belongsToMany(Role::class, 'subscribes_roles');
    }

    function templates()
    {
        return $this->hasMany(SubscribeTemplate::class);
    }

    function getStatusAttribute()
    {
        return $this->unsubscribes->contains(Auth::user()) ? '<i class="fa fa-minus"></i>' : '<i class="fa fa-check"></i>';
    }

    function getIsSubscribeAttribute()
    {
        return $this->unsubscribes->contains(Auth::user()) ? 0 : 1;
    }


    function getUpdateLinkAttribute()
    {
        return route('subscribes.update', $this->id);
    }

    function scopeForUser($q)
    {
        return $q->whereHas('roles', function ($q){
           $q->whereIn('roles.id', Auth::user()->roles->pluck('id'));
        });
    }

}
