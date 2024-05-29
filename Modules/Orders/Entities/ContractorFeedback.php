<?php

namespace Modules\Orders\Entities;

use App\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;


class ContractorFeedback extends Model
{
    protected $table = 'contractor_feedback';

    protected $fillable = [
        'content',
        'order_id',
        'user_id'
    ];

    function user()
    {
        return $this->belongsTo(User::class);
    }

    function order()
    {
        return $this->belongsTo(Order::class);
    }


    function scopeCurrentUser($q)
    {
        return $q->whereUserId(Auth::id());
    }
}
