<?php

namespace App\Support;

use App\System\SystemFunction;
use App\System\SystemModule;
use App\System\TaskStatusStamp;
use App\System\WorkSlip;
use App\User;
use App\Overrides\Model;

class TaskManager extends Model
{
   protected $table = 'task_manager';

   protected $fillable = [
       'task', 'type', 'user_id', 'preview_task', 'role', 'priority', 'system_module_id', 'system_function_id'
       ];

   protected $appends = [
       'status', 'preview_task', 'role_name', 'sum_hours', 'title_task',
        'function_name', 'module_name'
   ];

   static $types  = [
     'planned',
     'in_work',
     'complete',
     'cancel',
   ];

    static $types_lng  = [
        'Ожидает выполнения',
        'В работе',
        'Завершена',
        'Отменена',
    ];

   static $roles = [
        'Любой посетитель сайта',
        'Незарегистированный в системе',
        'Любой авторизованный',
        'Заказчик',
        'Исполнитель',
        'Веб виджет',
        'Админ',
        'Фин.админ',
        'Админ контента',
        'Другое',
   ];
    function user()
   {
       return $this->belongsTo(User::class);
   }

   function work_slips()
   {
       return $this->hasMany(WorkSlip::class, 'task_id')->with('user');
   }

   function system_module()
   {
     return $this->belongsTo(SystemModule::class);
   }

    function system_function()
    {
        return $this->belongsTo(SystemFunction::class);
    }

    function getModuleNameAttribute()
    {
        return $this->system_module->name ?? '';
    }

    function getFunctionNameAttribute()
    {
        return $this->system_function->name ?? '';
    }

    function status_history()
    {
        return $this->hasMany(TaskStatusStamp::class, 'task_id')->with('user');
    }

   function getStatusAttribute()
   {
       return self::$types_lng[$this->type];
   }

    function getRoleNameAttribute()
    {
        return self::$roles[$this->role];
    }

    static function getStatusKey($key)
    {
        return array_search($key, self::$types);
    }

    function getPreviewTaskAttribute()
    {

        return mb_strlen($this->task)  >  120 ? mb_substr($this->task, 0, 120) . '...' : $this->task;
    }

    function getTitleTaskAttribute()
    {

        return mb_strlen($this->task)  >  50 ? mb_substr($this->task, 0, 50) . '...' : $this->task;
    }

    function getSumHoursAttribute()
    {
        return $this->work_slips()->sum('hours') / 100;
    }

    function getPriorityAttribute($val)
    {
        return (string) $val;
    }


}
