<?php

namespace App\Machines;

use App\Machinery;
use App\Overrides\Model;

class MachineryEquipment extends Model
{
    protected $fillable = [
        'equipment_id',
        'machinery_id',
        'sum',
        'sum_hour'
    ];

    function machine()
    {
        return $this->belongsTo(Machinery::class);
    }

    function equipment()
    {
        return $this->belongsTo(Equipment::class);
    }
}
