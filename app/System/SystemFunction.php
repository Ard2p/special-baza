<?php

namespace App\System;

use App\Support\KnowledgeBase;
use App\Support\TaskManager;
use App\Overrides\Model;

class SystemFunction extends Model
{
    function system_module()
    {
        return $this->belongsTo(SystemModule::class);
    }

    function tasks()
    {
        return $this->hasMany(TaskManager::class);
    }

    function knowledges()
    {
        return $this->hasMany(KnowledgeBase::class);
    }
}
