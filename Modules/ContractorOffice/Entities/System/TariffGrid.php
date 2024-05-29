<?php

namespace Modules\ContractorOffice\Entities\System;

use App\Machinery;
use Illuminate\Database\Eloquent\Model;
use Modules\CompanyOffice\Services\BelongsToCompanyBranch;
use Spatie\EloquentSortable\SortableTrait;

class TariffGrid extends Model
{

    use BelongsToCompanyBranch, SortableTrait;


    public $timestamps = null;
    const WITH_DRIVER = 'with_driver';
    const WITHOUT_DRIVER = 'without_driver';

    protected $fillable = [
        'unit_compare_id',
        'min',
        'market_markup',
        'is_fixed',
        'type',
        'machinery_id',
        'machinery_type',
        'sort_order',
    ];

    public $sortable = [
        'order_column_name' => 'sort_order',
        'sort_when_creating' => false,
    ];

    protected $casts = [
        'is_fixed' => 'boolean'
    ];

   // protected $with = ['gridPrices'];


    function gridPrices()
    {
        return $this->hasMany(TariffGridPrice::class, 'tariff_grid_id');
    }


    function unitCompare()
    {
        return $this->belongsTo(TariffUnitCompare::class, 'unit_compare_id');
    }


    function machinery()
    {
        return $this->belongsTo(Machinery::class);
    }
}
