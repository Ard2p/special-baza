<?php

namespace Modules\PartsWarehouse\Entities\Warehouse;

use AjCastro\EagerLoadPivotRelations\EagerLoadPivotTrait;
use App\Directories\Unit;
use App\Machines\Brand;
use App\Machines\MachineryModel;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Modules\CompanyOffice\Entities\Company\CompanyBranch;
use Modules\PartsWarehouse\Entities\Stock\Item;

class WarehousePartSetPosition extends Pivot
{

    protected $table = 'warehouse_part_set_positions';

    protected $fillable = [
        'warehouse_part_set_id',
        'cb_warehouse_part_id',
        'count',
    ];

    public function warehouse_part_set()
    {
        return $this->belongsTo(WarehousePartSet::class);
    }

    public function part()
    {
        return $this->belongsTo(CompanyBranchWarehousePart::class,'cb_warehouse_part_id','id');
    }
}
