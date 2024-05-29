<?php

namespace App\Marketing\User;

use App\Overrides\Model;

class Participant extends Model
{
    protected $fillable = [
        'contest_id',
        'user_id',
        'up',
        'down',
        'current_rate',
        'photo',
        'description'
    ];
}
