<?php

namespace Modules\ContractorOffice\Entities\Vehicle\Shop;

use App\Machinery;
use Illuminate\Database\Eloquent\Model;
use Modules\CompanyOffice\Services\BelongsToCompanyBranch;
use Modules\CompanyOffice\Services\HasManager;
use Modules\CompanyOffice\Services\InternalNumbering;
use Modules\PartsWarehouse\Entities\PartsProvider;

class MachineryPurchase extends Model
{
    use BelongsToCompanyBranch, HasManager, InternalNumbering;


   // protected $with = ['operations'];

    protected $appends = ['amount'];

    protected $fillable = [
        'pay_type',
        'account_number',
        'account_date',
        'provider_id',
        'creator_id',
        'currency',
        'company_branch_id',
    ];

    function provider()
    {
        return $this->belongsTo(PartsProvider::class, 'provider_id');
    }
    function machine()
    {
        return $this->morphToMany(Machinery::class, 'owner', 'machinery_shop_characteristic', 'machinery_id');
    }

    function operations()
    {
        return $this->morphMany(OperationCharacteristic::class, 'owner');
    }

    function getAmountAttribute()
    {
        return $this->operations->sum('cost');
    }

}
