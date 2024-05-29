<?php

namespace Modules\PartsWarehouse\Entities\Stock;

use App\Directories\Unit;
use App\Service\RequestBranch;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Modules\CompanyOffice\Services\BelongsToCompanyBranch;
use Modules\Dispatcher\Entities\InvoiceItem;
use Modules\PartsWarehouse\Entities\Posting;
use Modules\PartsWarehouse\Entities\Shop\Parts\PartsSale;
use Modules\PartsWarehouse\Entities\Warehouse\CompanyBranchWarehousePart;
use Modules\PartsWarehouse\Entities\Warehouse\Part;
use Modules\PartsWarehouse\Entities\Warehouse\PartCustom;
use Modules\PartsWarehouse\Entities\Warehouse\WarehousePartSet;
use OwenIt\Auditing\Auditable;

class Item extends Model implements \OwenIt\Auditing\Contracts\Auditable
{

    use BelongsToCompanyBranch, Auditable;

    protected $table = 'stock_items';

    public $timestamps = false;

    protected $with = ['part', 'unit', 'stock'];

    protected $appends = ['placement_name'];

    protected $fillable = [
        'part_id',
        'stock_id',
        'unit_id',
        'amount',
        'serial_accounting',
        'cost_per_unit',
        'company_branch_id',
        'comment',
    ];

    protected $auditInclude = [
        'comment'
    ];

    protected $casts = [
        'serial_accounting' => 'boolean'
    ];

    function owner()
    {
        return $this->morphTo();
    }

    function oldOwner()
    {
        return $this->morphTo();
    }

    function part()
    {
        return $this->belongsTo(Part::class);
    }

    function stock()
    {
        return $this->belongsTo(Stock::class);
    }

    function unit()
    {
        return $this->belongsTo(Unit::class);
    }

    function serialNumbers()
    {
        return $this->hasMany(ItemSerial::class);
    }

    function getAvailableAmountAttribute()
    {
        return $this->getSameCount(true);
    }

    function scopeForBranch(Builder $q, $branch_id = null)
    {
        $branch_id = $branch_id ?: app(RequestBranch::class)->companyBranch->id;

        return $q->whereHas('stock', function ($q) use ($branch_id) {
            $q->forBranch($branch_id);
        });
    }

    function getPlacementNameAttribute()
    {
        return $this->getRecursiveStockName($this->stock);
    }

    function getRecursiveStockName(Stock $stock)
    {

        static $name = '';
        $name .= $name ? " - {$stock->name}" : $stock->name;
        if ($stock->parent) {
            return $this->getRecursiveStockName($stock->parent);
        }

        return $name;
    }

    static function getParts($ids = null, $stockId = [])
    {
        $items = self::query()
            ->with([
                'part.company_branches'
            ])
            ->whereHasMorph('owner', [Posting::class])
            ->groupBy(['stock_id', 'part_id'])
            ->forBranch();

        if ($stockId) {
            $items->whereIn('stock_id', $stockId);
        }

        if ($ids) {
            $items->whereIn('part_id', $ids);
        }

        $items = $items->get();

        $items->each->setAppends(['available_amount']);

        $items = $items->filter(function ($item) {
            return $item->available_amount > 0;
        })->map(function ($item) {

            $item->serial_numbers = ItemSerial::query()
                ->whereHas('item', function (Builder $q) use ($item) {
                    $q->where('part_id', $item->part_id)
                        ->where('stock_id', $item->stock_id)
                        ->whereHasMorph('owner', [Posting::class]);

                })->whereDoesntHave('saleItem')->get();

            return $item;
        });

        return $items->sortBy('part.name')->values()->all();
    }

    static function getPartsForRent(
        int $stockId = null,
        string $name = null,
        string $vendorCode = null,
        int $brandId = null,
        string $type = null
    ) {
        $items = self::query()
            ->with([
                'part.company_branches'
            ])
            ->whereHas('part.company_branches', function ($q) {
                $q->where('is_rented', true);
            })
            ->whereHasMorph('owner', [Posting::class])
            ->groupBy(['stock_id', 'part_id'])
            ->forBranch();

        if ($stockId != null) {
            $items = $items->where('stock_id', $stockId);
        }

        if ($type != null) {
            $items = $items->whereHas('part', function ($q) use ($type) {
                return $q->where('type', $type);
            });
        }

        if ($name != null) {
            $items = $items->whereHas('part', function ($q) use ($name) {
                return $q->where('name', 'LIKE', "%$name%");
            });
        }
        if ($vendorCode != null) {
            $items = $items->whereHas('part', function ($q) use ($vendorCode) {
                return $q->where('vendor_code', 'LIKE', "%$vendorCode%");
            });
        }
        if ($brandId != null) {
            $items = $items->whereHas('part', function ($q) use ($brandId) {
                return $q->where('brand_id', $brandId);
            });
        }

        $items = $items->get();

        $items->each->setAppends(['available_amount']);

        $items = $items->filter(function ($item) {
            return $item->available_amount > 0;
        })->map(function ($item) {

            $item->serial_numbers = ItemSerial::query()
                ->whereHas('item', function (Builder $q) use ($item) {
                    $q->where('part_id', $item->part_id)
                        ->where('stock_id', $item->stock_id)
                        ->whereHasMorph('owner', [Posting::class]);

                })->whereDoesntHave('saleItem')->get();

            return $item;
        });

        return $items->values()->all();
    }

    function getSameCount($inStock = false, Carbon $maxDate = null, $morphs = [Posting::class, PartsSale::class])
    {
        /** @var Builder $collection */
        $collection = self::query()->forBranch()
            ->where('part_id', $this->part_id);
        if ($inStock) {
            $collection->where('stock_id', $this->stock_id);
        }
        if ($maxDate) {

            $collection->whereHasMorph('owner', [Posting::class, PartsSale::class], function ($q) use ($maxDate) {
                $q->whereDate('date', '<=', $maxDate);
            });
        }

        $collection = $collection->get();
        $total = 0;

        $collection->each(function (Item $item) use (&$total) {
            if ($item->owner_type === Posting::class) {
                $total += $item->amount;
                // $total += $item->serialNumbers()->whereDoesntHave('saleItem')->count();
            } else {
                $total -= $item->amount;
                //   $total -= $item->serialNumbers()->whereHas('saleItem')->count();
            }

        });

        return $total;
    }

    function invoicePosition()
    {
        return $this->morphOne(InvoiceItem::class, 'owner');
    }
}
