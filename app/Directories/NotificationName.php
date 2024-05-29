<?php

namespace App\Directories;

use Illuminate\Database\Eloquent\Model;

class NotificationName extends Model
{
   protected $fillable = ['alias', 'name'];


    static function getId($alias)
    {
        return self::whereAlias($alias)->first()->id;
    }
}
