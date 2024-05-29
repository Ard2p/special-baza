<?php

namespace Modules\PartsWarehouse\Entities;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Modules\CompanyOffice\Services\BelongsToCompanyBranch;
use Modules\PartsWarehouse\Entities\Stock\Item;

class Posting extends Model
{

    use BelongsToCompanyBranch;

    protected $table = 'warehouse_postings';

    protected $fillable = [
        'parts_provider_id',
        'pay_type',
        'date',
        'account_number',
        'account_date',
        'company_branch_id',
    ];


    function provider()
    {
        return $this->belongsTo(PartsProvider::class, 'parts_provider_id');
    }

    function stockItems()
    {
        return $this->morphMany(Item::class, 'owner');
    }

    function scopeWithAmount(Builder $q, $part_id = null)
    {
        $q->withCount(['stockItems as amount' => function (Builder $query) use($part_id) {

            $query->select(DB::raw("SUM(`stock_items`.`amount`)"));
            if($part_id) {
                $query->where('stock_items.part_id', $part_id);
            }

        }]);
    }

    function scopeWithCost(Builder $q, $part_id = null)
    {
        $q->withCount(['stockItems as cost' => function (Builder $query) use($part_id) {
            $query->select(DB::raw("SUM(`stock_items`.`amount` * `stock_items`.`cost_per_unit`)"));
            if($part_id) {
                $query->where('stock_items.part_id', $part_id);
            }
        }]);
    }
}
