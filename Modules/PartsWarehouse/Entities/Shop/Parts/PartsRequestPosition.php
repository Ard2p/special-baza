<?php

namespace Modules\PartsWarehouse\Entities\Shop\Parts;

use App\Directories\Unit;
use Illuminate\Database\Eloquent\Model;
use Modules\PartsWarehouse\Entities\Warehouse\Part;

class PartsRequestPosition extends Model
{

    protected $table = 'shop_parts_requests_positions';
    public $timestamps = false;

    protected $fillable = [
        'parts_request_id',
        'part_id',
        'amount',
        'cost_per_unit',
    ];

    protected $with = ['part'];


    function partsRequest()
    {
        return $this->belongsTo(PartsRequest::class, 'parts_request_id');
    }

    function part()
    {
        return $this->belongsTo(Part::class);
    }
}
