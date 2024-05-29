<?php

namespace App\Machines;

use Illuminate\Database\Eloquent\Model;

class BusyType extends Model
{
    public $timestamps = false;

    protected $primaryKey = 'key';

    public $incrementing = false;

    protected $fillable = ['key'];

    protected $appends = ['name'];

    const TYPE_REPAIR = 'repair';
    const TYPE_MAINTENANCE = 'maintenance';
    const TYPE_APPRAISE = 'appraise';

    function getNameAttribute()
    {

        return trans('calendar_busy_types.' . $this->key);
    }

}
