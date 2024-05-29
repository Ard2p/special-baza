<?php

namespace App\Marketing\Mailing;

use App\User;
use Illuminate\Database\Eloquent\Model;

class PhoneList extends Model
{
    protected $fillable = [
        'phone',
        'user_id',
    ];

    protected $appends = ['user_name'];

    function lists()
    {
        return $this->belongsToMany(ListName::class, 'list_name_phone');
    }


    function user()
    {
        return $this->belongsTo(User::class);
    }

    function getUserNameAttribute()
    {

        return $this->user->id_with_email ??  'Нет в системе';
    }
}
