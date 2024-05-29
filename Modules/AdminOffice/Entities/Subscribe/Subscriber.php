<?php

namespace Modules\AdminOffice\Entities\Subscribe;

use App\User;
use Illuminate\Database\Eloquent\Model;

class Subscriber extends Model
{
    protected $fillable = [
        'email', 'user_id', 'status'
    ];

    function user()
    {
        return $this->belongsTo(User::class);
    }
}
