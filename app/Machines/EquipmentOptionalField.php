<?php

namespace App\Machines;

use App\Directories\Unit;
use Illuminate\Database\Eloquent\Model;

class EquipmentOptionalField extends Model
{
    protected $fillable = [
        'equipment_id',
        'name',
        'unit_id',
        'field_type'
    ];

    function equipment()
    {
        return $this->belongsTo(Equipment::class);
    }

    function unit()
    {
        return $this->belongsTo(Unit::class);
    }
}
