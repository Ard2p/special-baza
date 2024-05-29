<?php

namespace App\User;

use App\User;
use App\Overrides\Model;

class SendingSubscribe extends Model
{
    protected $fillable = [
        'user_id',
        'subscribe_template_id',
        'confirm_status',
        'is_watch',
        'watch_at',
        'hash',
    ];


    function template()
    {
        return $this->belongsTo(SubscribeTemplate::class, 'subscribe_template_id');
    }

    function user()
    {
        return $this->belongsTo(User::class);
    }
}
