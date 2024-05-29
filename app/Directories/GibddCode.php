<?php

namespace App\Directories;

use App\Support\Region;
use App\Overrides\Model;

class GibddCode extends Model
{
    public  $timestamps = false;

    protected  $fillable = ['region_id', 'code'];

    function region()
    {
        return $this->hasOne(Region::class, 'id', 'region_id');
    }
}
