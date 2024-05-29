<?php

namespace Modules\PartsWarehouse\Entities\Warehouse;

use AjCastro\EagerLoadPivotRelations\EagerLoadPivotTrait;
use App\Directories\Unit;
use App\Machines\Brand;
use App\Machines\MachineryModel;
use App\Service\RequestBranch;
use App\Overrides\Model;
use Modules\CompanyOffice\Entities\Company\CompanyBranch;
use Modules\Orders\Entities\Service\ServiceCenter;
use Modules\PartsWarehouse\Entities\Shop\Parts\PartsSale;
use Modules\PartsWarehouse\Entities\Stock\Item;

class Part extends Model
{
    //use EagerLoadPivotTrait;

    protected $table = 'warehouse_parts';

    protected $fillable = [
        'name',
        'vendor_code',
        'brand_id',
        'group_id',
        'unit_id',
        'part_analogue_group_id',
        'images',
    ];

    protected $appends = ['full_name'];

    protected $with = ['unit', 'models', 'brand'];

    protected $casts = [
        'images' => 'array'
    ];

    public function company_branches()
    {
        return $this->belongsToMany(CompanyBranch::class, 'company_branches_warehouse_parts')
            ->using(CompanyBranchWarehousePart::class)
            ->withPivot([
                'id',
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
            ]);
    }

    function getSameCountForBranch($branch_id = null)
    {
        $branch_id = $branch_id ?: app(RequestBranch::class)->companyBranch->id;

        $item = $this->stockItems()->forBranch($branch_id)->first();

        return $item ? $item->getSameCount() : 0;
    }

    function getRentedCount()
    {
        return CompanyBranchWarehousePart::query()->where('part_id', $this->id)
            ->whereHas('warehouse_part_sets.orders')
            ->with(['warehouse_part_sets' => fn($q) => $q->whereHas('orders')])
            ->forBranch()
            ->get()
            ->sum(fn (CompanyBranchWarehousePart $part) => $part->warehouse_part_sets->sum('pivot.count'));
    }

    function getInServicesCount()
    {
        return (int) Item::query()
            ->where('part_id', $this->id)
            ->whereHasMorph('owner', [ServiceCenter::class], function ($q) {
            $q->forBranch()->whereNotIn('status_tmp', [ServiceCenter::STATUS_DONE, ServiceCenter::STATUS_ISSUED]);
        })->sum('amount');
    }

    function getSalesCount()
    {
        return (int) Item::query()
            ->where('part_id', $this->id)
            ->whereHasMorph('owner', [PartsSale::class], function ($q) {
                $q->forBranch();
            })->sum('amount');
    }


    function stockItems()
    {
        return $this->hasMany(Item::class);
    }

    function group()
    {
        return $this->belongsTo(PartsGroup::class, 'group_id');
    }

    function brand()
    {
        return $this->belongsTo(Brand::class);
    }

    function unit()
    {
        return $this->belongsTo(Unit::class);
    }

    function models()
    {
        return $this->belongsToMany(MachineryModel::class, 'warehouse_parts_machinery_models')->using(PartMachineryModelPivot::class)->withPivot('serial_numbers');
    }

    function analogueGroup()
    {
        return $this->belongsTo(PartAnalogueGroup::class, 'part_analogue_group_id');
    }


    function setAnalogue(PartAnalogueGroup $analogueGroup = null)
    {
        if ($analogueGroup === null) {

            if ($this->analogueGroup && $this->analogueGroup->parts()->count() === 1) {

                $this->analogueGroup->delete();

            }

            $this->part_analogue_group_id = null;

        } else {

            $this->analogueGroup()->associate($analogueGroup);

        }

        $this->save();

        return $this;

    }

    function getAnalogueGroup()
    {
        if(!$this->analogueGroup) {
            $group = PartAnalogueGroup::create();
            $this->analogueGroup()->associate($group)->save();
        }

        return $group ?? $this->analogueGroup;
    }

    function getFullNameAttribute()
    {
         return $this->brand ? "{$this->brand->name} - {$this->name}" : $this->name;
    }
}
