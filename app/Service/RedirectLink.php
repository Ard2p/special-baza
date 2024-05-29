<?php

namespace App\Service;

use App\Overrides\Model;

class RedirectLink extends Model
{
   protected $fillable = [
       'hash', 'link'
   ];
}
