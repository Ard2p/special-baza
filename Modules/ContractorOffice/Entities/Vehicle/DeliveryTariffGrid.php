<?php

namespace Modules\ContractorOffice\Entities\Vehicle;

use App\Machinery;
use Illuminate\Database\Eloquent\Model;

class DeliveryTariffGrid extends Model
{

    protected $table = 'machinery_delivery_tariff_grid';


    public $timestamps = false;

    protected $fillable = [
        'min',
        'is_fixed',
        'type',
        'machinery_id',
    ];

    //protected $with = ['grid_prices'];

    protected $casts = [
        'is_fixed' => 'boolean'
    ];

    function grid_prices()
    {
        return $this->hasMany(DeliveryPrice::class, 'delivery_tariff_grid_id');
    }

    function machinery()
    {
        return $this->belongsTo(Machinery::class);
    }
}
