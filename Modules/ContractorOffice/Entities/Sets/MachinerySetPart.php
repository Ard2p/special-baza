<?php

namespace Modules\ContractorOffice\Entities\Sets;

use Illuminate\Database\Eloquent\Model;
use Modules\PartsWarehouse\Entities\Warehouse\Part;

class MachinerySetPart extends Model
{
    protected $fillable = [
        'machinery_set_equipment_id',
        'part_id',
        'count',
    ];

    function part()
    {
        return $this->belongsTo(Part::class);
    }

    function machinerySetEquipment()
    {
        return $this->belongsTo(MachinerySetEquipment::class);
    }

    function getPartNameAttribute()
    {
        return $this->part?->name;
    }
}
