<?php

namespace App\Traits;

use App\Models\User;
use Illuminate\Support\Facades\Auth;

/**
 * Трейт отслеживания изменений моделей пользователем
 * @property int $created_by_id Користувач, що створив картку;
 * @property int $updated_by_id Користувач, що вніс останні зміни до картки.
 * @property User $created_by Користувач, що створив картку;
 * @property User $updated_by Користувач, що вніс останні зміни до картки.
 */
trait ResponsibleUser
{

    protected static function bootResponsibleUser()
    {
         static::creating(function (self $model) {
             if(Auth::check()) {
                 $model->createdBy()->associate(Auth::user());
                 $model->updatedBy()->associate(Auth::user());
             }

             return $model;
         });

        static::updating(function (self $model) {
            if(Auth::check()) {
                $model->updatedBy()->associate(Auth::user());
            }
            return $model;
        });
    }

    function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by_id');
    }

    function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by_id');
    }
}
