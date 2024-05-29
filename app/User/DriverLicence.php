<?php

namespace App\User;

use App\Overrides\Model;

class DriverLicence extends Model
{

    public $timestamps = false;

    protected $fillable = [
        'serial',
        'date_of_issue',
        'valid_until',
        'issued_by',
    ];

    function owner()
    {
        return $this->morphTo();
    }
}
