<?php

namespace Modules\PartsWarehouse\Entities\Shop\Parts;

use App\Machines\Sale;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Modules\CompanyOffice\Services\BelongsToCompanyBranch;
use Modules\CompanyOffice\Services\HasManager;
use Modules\Dispatcher\Entities\Customer;
use Modules\Dispatcher\Entities\Lead;
use Modules\PartsWarehouse\Entities\Posting;
use Modules\PartsWarehouse\Entities\Stock\Item;
use Modules\PartsWarehouse\Entities\Stock\ItemSerial;
use Modules\PartsWarehouse\Transformers\StockItemResource;

class PartsRequest extends Model
{

    use BelongsToCompanyBranch, HasManager;

    protected $table = 'shop_parts_requests';

    protected $fillable = [
        'date',
        'customer_id',
        'phone',
        'pay_type',
        'email',
        'contact_person',
        'status',
        'reject_type',
        'user_id',
        'company_branch_id',
    ];

    protected $dates = ['date'];

    protected $with = ['positions', 'customer', 'manager', 'sales'];

    protected $appends = ['status_lng'];


    function getStatusLngAttribute()
    {
        $array = Lead::getStatuses();

        $key = array_search($this->status, array_column($array, 'value'));

        return $array[$key]['name'] ?? '';
    }

    function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    function sales()
    {
        return $this->hasMany(PartsSale::class, 'parts_request_id');
    }


    function positions()
    {
        return $this->hasMany(PartsRequestPosition::class);
    }

    function getAvailableParts()
    {
        $ids = $this->positions()->pluck('part_id')->toArray();

        return Item::getParts($ids);
    }
}
