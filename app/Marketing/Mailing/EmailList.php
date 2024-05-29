<?php

namespace App\Marketing\Mailing;

use App\Marketing\SubmitContactForm;
use App\User;
use Illuminate\Database\Eloquent\Model;

class EmailList extends Model
{
    protected $fillable = [
        'email',
        'user_id',
    ];

    protected $appends = ['user_name'];

    function lists()
    {
        return $this->belongsToMany(ListName::class, 'list_name_email');
    }

    function submit_forms()
    {
        return $this->hasMany(SubmitContactForm::class);
    }

    function user()
    {
        return $this->belongsTo(User::class);
    }

    function getUserNameAttribute()
    {

        return $this->user->id_with_email ?? 'Нет в системе';
    }

    function getCommentAttribute()
    {

    }
}
