<?php

namespace Modules\PartsWarehouse\Entities\Warehouse;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Pivot;

class PartMachineryModelPivot extends Pivot
{
    protected $fillable = [
        'serial_numbers',
        'machinery_model_id',
        'part_id',
    ];

    protected $casts = [
        'serial_numbers' => 'array'
    ];

}
