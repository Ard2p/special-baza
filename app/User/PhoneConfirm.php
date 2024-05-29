<?php

namespace App\User;

use App\Overrides\Model;

class PhoneConfirm extends Model
{
   protected $fillable = [
       'user_id',
       'phone',
       'token',
       ];
}
