<?php

namespace Modules\ContractorOffice\Entities\Vehicle\Shop;

use App\Machines\Brand;
use App\Machines\MachineryModel;
use App\Machines\Type;
use Illuminate\Database\Eloquent\Model;

class MachinerySaleRequestPosition extends Model
{

    protected $table = 'machinery_sale_requests_positions';

    public $timestamps = false;


    //protected $with = ['category'];

    protected $fillable = [
        'machinery_sale_request_id',
        'category_id',
        'model_id',
        'brand_id',
        'year',
        'engine_hours',
        'comment',
        'amount',
    ];

    function saleRequest()
    {
        return $this->belongsTo(MachinerySaleRequest::class, 'machinery_sale_request_id');
    }

    function category()
    {
        return $this->belongsTo(Type::class, 'category_id');
    }

    function brand()
    {
        return $this->belongsTo(Brand::class);
    }

    function model()
    {
        return $this->belongsTo(MachineryModel::class, 'model_id');
    }
}
