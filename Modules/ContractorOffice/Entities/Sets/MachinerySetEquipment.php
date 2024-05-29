<?php

namespace Modules\ContractorOffice\Entities\Sets;

use App\Machines\MachineryModel;
use App\Machines\Type;
use App\Overrides\Model;

class MachinerySetEquipment extends Model
{

    protected $table = 'machinery_set_equipment';

    public $timestamps = false;

    protected $fillable = [
        'model_id',
        'machinery_set_id',
        'category_id',
        'brand_id',
        'count'
    ];

   // protected $with = ['parts'];

    function machineryModel()
    {
        return $this->belongsTo(MachineryModel::class, 'model_id');
    }

    function category()
    {
        return $this->belongsTo(Type::class, 'category_id');
    }

    function machinerySet()
    {
        return $this->belongsTo(MachinerySet::class);
    }


    function parts()
    {
        return $this->hasMany(MachinerySetPart::class);
    }
}
