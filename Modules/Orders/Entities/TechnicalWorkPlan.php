<?php

namespace Modules\Orders\Entities;

use App\Machinery;
use App\Machines\Type;
use Illuminate\Database\Eloquent\Model;

class TechnicalWorkPlan extends Model
{

    public $timestamps = false;

    protected $fillable = [
        'active',
        'type',
        'machinery_id',
        'category_id',
        'duration',
        'duration_between_works',
        'duration_plan',
    ];

    protected $casts = [
        'active' => 'boolean'
    ];

    function category()
    {
        return $this->belongsTo(Type::class, 'category_id');
    }

    function machinery()
    {
        return $this->belongsTo(Machinery::class);
    }
}
