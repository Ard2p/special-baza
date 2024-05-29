<?php

namespace App\Marketing\User;

use Illuminate\Database\Eloquent\Model;

class ContestGuestVoting extends Model
{
    protected $fillable = [
        'ip',
        'counter',
        'contest_id'
    ];
}
