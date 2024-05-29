<?php

namespace Modules\PartsWarehouse\Entities\Warehouse;

use Illuminate\Database\Eloquent\Relations\Pivot;
use Modules\CompanyOffice\Entities\Company\CompanyBranch;
use Modules\CompanyOffice\Services\BelongsToCompanyBranch;
use Modules\ContractorOffice\Entities\System\TariffGrid;

class CompanyBranchWarehousePart extends Pivot
{

    use BelongsToCompanyBranch;

    protected $table = 'company_branches_warehouse_parts';

    protected $fillable = [
        'company_branch_id',
        'part_id',
        'min_order',
        'min_order_type',
        'change_hour',
        'currency',
        'tariff_type',
        'is_rented',
        'is_for_sale',
        'default_sale_cost',
        'default_sale_cost_cashless',
        'default_sale_cost_cashless_vat',
        'name',
        'vendor_code',
    ];

    protected $casts = [
        'is_for_sale' => 'boolean',
        'is_rented' => 'boolean',
    ];

    protected $with = ['prices.gridPrices'];

    public function company_branch()
    {
        return $this->belongsTo(CompanyBranch::class);
    }

    public function warehouse_part_sets()
    {
        return $this->belongsToMany(WarehousePartSet::class, 'warehouse_part_set_positions','cb_warehouse_part_id')
            ->using(WarehousePartSetPosition::class)
            ->withPivot(['count']);
    }

    public function part()
    {
        return $this->belongsTo(Part::class);
    }

    function prices()
    {
        return $this->morphMany(TariffGrid::class, 'machinery')->with('gridPrices');
    }

}
