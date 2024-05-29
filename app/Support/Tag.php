<?php

namespace App\Support;

use App\Machines\Type;
use App\Overrides\Model;
use App\System\OrderableModel;

class Tag extends Model
{
    use OrderableModel;
    protected $fillable = ['name'];

    function types()
    {
        return $this->belongsToMany(Type::class,'tag_type');
    }
}
