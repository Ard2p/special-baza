<?php

namespace Modules\RestApi\Entities\Auth;

use App\Overrides\Model;

class GuestPhoneConfirm extends Model
{
    protected $fillable = [
        'phone',
        'code',
        'hash',
    ];

    function checkActual()
    {
        return now()->diffInSeconds($this->created_at) < 120;
    }
}
