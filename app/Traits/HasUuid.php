<?php

namespace App\Traits;

use Ramsey\Uuid\Uuid;

trait HasUuid
{

    static function bootHasUuid()
    {
        static::creating(function (self $model) {
            if(!$model->uuid) {
                $model->uuid = Uuid::uuid4();
            }

            return $model;
        });
    }

    function initializeHasUuid()
    {
        $this->primaryKey = 'uuid';

        $this->keyType = 'string';

        $this->incrementing = false;

        if (! in_array('uuid', $this->fillable)) {
            $this->fillable[] = 'uuid';
            $this->casts['uuid'] = 'string';
        }
    }
}