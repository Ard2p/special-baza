<?php

namespace Modules\PartsWarehouse\Entities\Warehouse;

use App\Machines\Type;
use App\Overrides\Model;
use Modules\CompanyOffice\Entities\Company\CompanyBranch;
use Modules\CompanyOffice\Services\BelongsToCompanyBranch;
use Modules\ContractorOffice\Entities\Vehicle\MachineryBase;
use Modules\Orders\Entities\MachineryStamp;
use Modules\Orders\Entities\Order;
use Modules\Orders\Entities\OrderComponent;

class WarehousePartSet extends Model
{

    use BelongsToCompanyBranch;

    protected $table = 'warehouse_part_sets';

    protected $fillable = [
        'id',
        'type_id',
        'company_branch_id',
        'machinery_base_id',
        'name',
        'edited'
    ];

    protected $casts = [
      'edited' => 'boolean'
    ];

    public function parts()
    {
        return $this->belongsToMany(CompanyBranchWarehousePart::class,
            'warehouse_part_set_positions',
            'warehouse_part_set_id',
            'cb_warehouse_part_id'
        )
            ->using(WarehousePartSetPosition::class)
            ->withPivot(['count']);
    }

    public function machinery_base()
    {
        return $this->belongsTo(MachineryBase::class);
    }

    public function type()
    {
        return $this->belongsTo(Type::class);
    }

    public function _type()
    {
        return $this->belongsTo(Type::class, 'type_id', 'id');
    }

    public function company_branch()
    {
        return $this->belongsTo(CompanyBranch::class);
    }


    function order_timestamps()
    {
        return $this->morphMany(MachineryStamp::class,'machinery');
    }

    function orders()
    {
        return $this->morphToMany(Order::class, 'worker', 'order_workers')->withPivot('amount', 'date_from', 'date_to');
    }

    function subOwner()
    {
        return $this->morphTo();
    }

    function wialon_telematic()
    {
        return $this->morphTo();
    }
}
