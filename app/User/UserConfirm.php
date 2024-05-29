<?php

namespace App\User;

use App\Notifications\EmailConfirm;
use Carbon\Carbon;
use App\Overrides\Model;
use Illuminate\Support\Facades\Mail;

class UserConfirm extends Model
{

    protected $fillable = [
      'email', 'token'
    ];


}
