<?php

namespace Modules\Telephony\Entities;


use App\User;
use Illuminate\Database\Eloquent\Model;

class PhoneProposal extends Model
{
    protected $fillable = [
        'proposal_id',
        'user_id',
        'phone',
    ];

    protected $appends = [
        'user_url', 'proposal_url'
    ];
    function user()
    {
        return $this->belongsTo(User::class);
    }

    function proposal()
    {
        return $this->belongsTo(Proposal::class);
    }

    function getUserUrlAttribute()
    {
        return "https://office.trans-baza.ru/users/{$this->user_id}/edit";
    }

    function getProposalUrlAttribute()
    {
        return route('order.show', $this->proposal_id);
    }
}
