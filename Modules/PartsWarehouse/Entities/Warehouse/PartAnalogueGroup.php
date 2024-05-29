<?php

namespace Modules\PartsWarehouse\Entities\Warehouse;

use Illuminate\Database\Eloquent\Model;

class PartAnalogueGroup extends Model
{

    protected $table = 'warehouse_part_analogue_groups';
    public $timestamps = false;

    protected $fillable = [];

    function parts()
    {
        return $this->hasMany(Part::class);
    }
}
