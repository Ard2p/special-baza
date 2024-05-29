<?php

namespace Modules\AdminOffice\Entities;

use App\User;
use Illuminate\Database\Eloquent\Model;

class YandexPhoneCredential extends Model
{
    protected $fillable = ['login', 'password', 'enable', 'user_id'];

    function user()
    {
        return $this->belongsTo(User::class);
    }
}
