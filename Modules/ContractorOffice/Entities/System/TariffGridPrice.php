<?php

namespace Modules\ContractorOffice\Entities\System;

use Illuminate\Database\Eloquent\Model;

class TariffGridPrice extends Model
{


    protected $table = 'tariff_grid_price';

    public $timestamps = false;
    protected $fillable = [
        'price',
        'price_type',
        'tariff_grid_id',
    ];


    function tariffGrid()
    {
        return $this->belongsTo(TariffGrid::class, 'tariff_grid_id');
    }
}
