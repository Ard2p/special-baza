<?php

namespace Modules\PartsWarehouse\Entities\Stock;

use App\Overrides\Model;
use Modules\CompanyOffice\Services\BelongsToCompanyBranch;
use Modules\ContractorOffice\Entities\Vehicle\MachineryBase;
use Modules\RestApi\Traits\HasCoordinates;

class Stock extends Model
{

    use BelongsToCompanyBranch, HasCoordinates;

    protected $fillable = [
        'name',
        'address',
        'coordinates',
        'machinery_base_id',
        'company_branch_id',
        'parent_id',
        'onec_info'
    ];

    protected $with = ['children'];

    protected $casts = [
        'onec_info' => 'object'
    ];

    protected $appends = ['items_count', 'label', 'onec_uuid'];

    function items()
    {
        return $this->hasMany(Item::class, 'stock_id');
    }

    function children()
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    function parent()
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    function base()
    {
        return $this->belongsTo(MachineryBase::class);
    }

    function getItemsCountAttribute()
    {
        return $this->items()->count();
    }

    function getItemsCountRecursive()
    {
        $items = $this->items_count;

        $this->children->each(function ($item) use (&$items) {
            $items += $item->getItemsCountRecursive();
        });

        return $items;
    }

    function getLabelAttribute()
    {
        return $this->name;
    }

    public function getOnecUuidAttribute()
    {
        return $this->onec_info?->Ref_Key;
    }
}
