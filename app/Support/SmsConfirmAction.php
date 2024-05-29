<?php

namespace App\Support;

use App\User;
use App\Overrides\Model;

class SmsConfirmAction extends Model
{
    protected $fillable = [
        'action',
        'model',
        'code',
        'user_id'
    ];

    protected static function boot()
    {
        parent::boot();

        self::created(function ($model) {
            $model->user->sendSmsNotification("Код подтверждения операции {$model->code}");
        });
    }


    function user()
    {
        return $this->belongsTo(User::class);
    }

    function isCorrect($code)
    {
        return (string) $this->code === $code;
    }
}
