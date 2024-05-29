<?php

namespace App\System;

use App\Machinery;
use App\User;
use App\Overrides\Model;
use Modules\Orders\Entities\Order;

class Audit extends Model
{

    protected $appends = [
        'event_name',
      /*  'new_values',
        'old_values',*/
    ];
    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
        'created_at' => 'datetime',
    ];
    function user()
    {
        return $this->belongsTo(User::class);
    }


    function auditableModel()
    {
        return $this->belongsTo($this->auditable_type, 'auditable_id');
    }


    function getEventNameAttribute()
    {


        switch ($this->event){
            case 'created':
                $name = 'Создание';
                break;
            case 'updated':
                $name = 'Редактирование';
                break;
            case 'deleted':
                $name = 'Удаление';
                break;
        }
        return $name ?? '';
    }

    function getAuditableTypeNameAttribute()
    {
        switch ($this->auditable_type){
            case Machinery::class:
                $name = 'Техника';
                break;
            case User::class:
                $name = 'Профиль';
                break;
            case Order::class:
                $name = 'Заявка';
                break;
            default:
                $name = 'Не определно';
                break;
        }
        return $name ?? '';
    }

    function getOldAttribute()
    {
        return view('admin.audits.old_values', ['item' => $this])->render();
    }

    function getNewAttribute()
    {
        return view('admin.audits.new_values', ['item' => $this])->render();
    }

    function getUserEmailAttribute()
    {
        return $this->user->id_with_email ?? '';
    }
/*
    function getOldValuesAttribute($values)
    {
        return collect(json_decode($values, true));
    }

    function getNewValuesAttribute($values)
    {
        return collect(json_decode($values, true));
    }*/
}
