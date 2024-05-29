<?php

namespace App\Marketing\User;

use App\Overrides\Model;

class UserRelation extends Model
{
    protected $fillable = [
        'relation_access',
        'name',
    ];
}
