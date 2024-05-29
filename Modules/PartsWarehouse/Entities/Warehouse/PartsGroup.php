<?php

namespace Modules\PartsWarehouse\Entities\Warehouse;

use App\Overrides\Model;

class PartsGroup extends Model
{

    protected $table = 'warehouse_parts_groups';

    protected $fillable = [
        'name',
        'parent_id',
        'images'
    ];

    protected $casts = [
        'images' => 'array'
    ];

    protected $appends =['label'];

    function children()
    {
        return $this->hasMany(self::class, 'parent_id');
    }
    function parent()
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    function getLabelAttribute()
    {
        return $this->name;
    }
}
