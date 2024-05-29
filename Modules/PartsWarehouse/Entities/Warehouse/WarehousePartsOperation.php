<?php

namespace Modules\PartsWarehouse\Entities\Warehouse;

use App\Machines\Type;
use Illuminate\Database\Eloquent\Model;
use Modules\CompanyOffice\Entities\Company\CompanyBranch;
use Modules\ContractorOffice\Entities\Vehicle\MachineryBase;
use Modules\Orders\Entities\MachineryStamp;
use Modules\Orders\Entities\Order;
use Modules\Orders\Entities\OrderComponent;

class WarehousePartsOperation extends Model
{
    protected $table = 'warehouse_parts_operations';

    protected $fillable = [
        'company_branches_warehouse_part_id',
        'order_worker_id',
        'type',
        'count',
        'cost_per_unit',
        'begin_date',
        'end_date',
    ];

    public function company_branches_warehouse_part()
    {
        return $this->belongsTo(CompanyBranchWarehousePart::class);
    }

    public function order_worker()
    {
        return $this->belongsTo(OrderComponent::class);
    }
}
