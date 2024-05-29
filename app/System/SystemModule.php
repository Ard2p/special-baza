<?php

namespace App\System;

use App\Support\KnowledgeBase;
use App\Overrides\Model;

class SystemModule extends Model
{
    protected $fillable  = ['name', 'alias'];
    protected $with = ['module_functions'];

    function module_functions()
    {
        return $this->hasMany(SystemFunction::class);
    }

    function content()
    {
        return $this->hasMany(KnowledgeBase::class);
    }
}
