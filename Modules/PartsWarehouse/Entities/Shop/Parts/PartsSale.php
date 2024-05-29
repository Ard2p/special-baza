<?php

namespace Modules\PartsWarehouse\Entities\Shop\Parts;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Modules\CompanyOffice\Entities\Company\DocumentsPack;
use Modules\CompanyOffice\Services\BelongsToCompanyBranch;
use Modules\CompanyOffice\Services\HasContacts;
use Modules\CompanyOffice\Services\HasManager;
use Modules\CompanyOffice\Services\InternalNumbering;
use Modules\ContractorOffice\Entities\Vehicle\MachineryBase;
use Modules\Dispatcher\Entities\Customer;
use Modules\Dispatcher\Entities\DispatcherInvoice;
use Modules\Dispatcher\Entities\Lead;
use Modules\Orders\Entities\OrderDocument;
use Modules\Orders\Services\OrderTrait;
use Modules\PartsWarehouse\Entities\Stock\Item;

class PartsSale extends Model
{

    use HasManager, BelongsToCompanyBranch, HasContacts, InternalNumbering, OrderTrait;

    protected $table = 'shop_parts_sales';

    protected $fillable = [
        'title',
        'status',
        'source',
        'date',
        'parts_request_id',
        'customer_id',
        'creator_id',
        'company_branch_id',
        'external_id',
        'base_id',
        'internal_number',
        'documents_pack_id',
    ];

    protected $with = [
        'items',
        'customer',
        'manager',
    ];

    protected $appends = ['status_lng'];

    function getTitleAttribute($val)
    {
        $name = trans('mails/mails_list.order_id', ['id' => $this->internal_number]);

        return "$name {$this->customer->company_name}";
    }
    function getStatusLngAttribute()
    {
        $array = Lead::getStatuses();

        $key = array_search($this->status, array_column($array, 'value'));

        return $array[$key]['name'] ?? '';
    }

    function documents()
    {
        return $this->morphMany(OrderDocument::class, 'order');
    }

    function documentsPack()
    {
        return $this->belongsTo(DocumentsPack::class);
    }

    function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    function items()
    {
        return $this->morphMany(Item::class, 'owner');
    }

    function contractorRequisite()
    {
        return $this->morphTo();
    }

    function base()
    {
        return $this->belongsTo(MachineryBase::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\MorphMany
     */
    function invoices()
    {
        return $this->morphMany(DispatcherInvoice::class, 'owner');
    }

    function partsRequest()
    {
        return $this->belongsTo(PartsRequest::class);
    }

    function scopeWithAmount(Builder $q, $part_id = null)
    {
        $q->withCount(['items as amount' => function (Builder $query) use($part_id) {

            $query->select(DB::raw("SUM(`stock_items`.`amount`)"));
            if($part_id) {
                $query->where('stock_items.part_id', $part_id);
            }

        }]);
    }

    function scopeWithCost(Builder $q, $part_id = null)
    {
        $q->withCount(['items as cost' => function (Builder $query) use($part_id) {
            /*SELECT COUNT(*) FROM `stock_item_serials` WHERE `stock_item_serials`.`item_id` = `stock_items`.`id`*/
            $query->select(DB::raw("SUM(`stock_items`.`amount`  * `stock_items`.`cost_per_unit`)"));
            if($part_id) {
                $query->where('stock_items.part_id', $part_id);
            }
        }]);
    }


    function scopeWithPaidInvoiceSum(Builder $builder)
    {
        return $builder->withCount([
            'invoices as paid_sum' => function ($q) {
                $q->select(\DB::raw("SUM(`paid_sum`)"));
            }
        ]);
    }
}
