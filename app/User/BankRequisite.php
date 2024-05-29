<?php

namespace App\User;

use App\Overrides\Model;

class BankRequisite extends Model
{

    protected $fillable = [
        'name',
        'bik',
        'ks',
        'owner_type',
        'owner_id',
        'rs'
    ];

    function owner()
    {
        return $this->morphTo();
    }
}
