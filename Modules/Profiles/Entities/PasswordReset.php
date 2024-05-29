<?php

namespace Modules\Profiles\Entities;

use App\Overrides\Model;

class PasswordReset extends Model
{
    public $timestamps = false;
    protected $fillable = [
        'token',
        'email',
        'created_at'
    ];
}
