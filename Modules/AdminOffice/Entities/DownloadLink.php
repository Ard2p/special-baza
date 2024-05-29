<?php

namespace Modules\AdminOffice\Entities;

use App\User;
use App\Overrides\Model;

class DownloadLink extends Model
{
    protected $fillable = ['link_hash', 'user_id'];


    function user()
    {
        return $this->belongsTo(User::class);
    }

}
