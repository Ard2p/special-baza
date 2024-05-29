<?php

namespace App\Marketing\User;

use App\Role;
use App\User;
use Illuminate\Database\Eloquent\Model;
use App\System\OrderableModel;

class Contest extends Model
{

    use OrderableModel;
    protected $fillable = [
        'name',
        'title',
        'keywords',
        'description',
        'photo',
        'content',
        'from',
        'to',
        'type',
    ];


    function roles()
    {
       return $this->belongsToMany(Role::class, 'contests_roles');
    }

    function user_relations()
    {
        return $this->belongsToMany(UserRelation::class, 'contests_user_relations');
    }

    function user_voting()
    {
        return $this->belongsToMany(User::class, 'contests_voting');
    }

    function guest_voting()
    {
        return $this->belongsToMany(ContestGuestVoting::class, 'contest_guest_voting');
    }

    function participants()
    {
        return $this->hasMany(Participant::class);
    }
}
