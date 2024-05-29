<?php

namespace Modules\ContractorOffice\Entities\Vehicle\Shop;

use App\Machinery;
use Illuminate\Database\Eloquent\Model;

class OperationCharacteristic extends Model
{

    protected $table = 'machinery_shop_characteristic';


    public $timestamps = false;

    protected $fillable = [
        'owner_type',
        'application_id',
        'machinery_id',
        'owner_id',
        'cost',
        'engine_hours',
        'type',
    ];

  //  protected $with = ['machine'];


    function owner()
    {
        return $this->morphTo();
    }

    function machine()
    {
        return $this->belongsTo(Machinery::class, 'machinery_id');
    }
}
