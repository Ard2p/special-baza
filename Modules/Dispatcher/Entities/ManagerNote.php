<?php

namespace Modules\Dispatcher\Entities;

use App\User;
use Illuminate\Database\Eloquent\Model;

class ManagerNote extends Model
{
    protected $fillable = [
        'text',
        'color',
        'manager_id',
        'owner_id'
    ];

    function owner()
    {
        return $this->morphTo();
    }

    function manager()
    {
        return $this->belongsTo(User::class, 'manager_id');
    }
}
