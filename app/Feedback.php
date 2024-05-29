<?php

namespace App;

use App\Overrides\Model;

class Feedback extends Model
{

    protected $fillable = ['user_id', 'is_performer', 'proposal_id', 'rate', 'comment',];

    function scopeCheckUnique($q, $user_id, $proposal_id)
    {
        return $q->where('user_id', $user_id)->where('proposal_id', $proposal_id);
    }
}
