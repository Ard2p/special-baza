<?php

namespace Modules\ContractorOffice\Entities\Vehicle;

use Illuminate\Database\Eloquent\Model;

class DeliveryPrice extends Model
{

    protected $table = 'machinery_delivery_prices';

    public $timestamps = false;

    protected $fillable = [
        'price',
        'price_type',
        'tariff_delivery_grid_id',
    ];

    function deliveryGrid()
    {
        return $this->belongsTo(DeliveryTariffGrid::class, 'delivery_tariff_grid_id');
    }
}
