<?php

namespace Modules\ContractorOffice\Entities\Vehicle\Shop;

use App\Machinery;
use Illuminate\Database\Eloquent\Model;
use Modules\CompanyOffice\Entities\Company\SaleContract;
use Modules\CompanyOffice\Services\BelongsToCompanyBranch;
use Modules\CompanyOffice\Services\HasManager;
use Modules\CompanyOffice\Services\InternalNumbering;
use Modules\ContractorOffice\Services\Shop\SaleService;
use Modules\Orders\Entities\OrderDocument;
use Modules\Orders\Services\OrderTrait;

class MachinerySale extends Model
{

    use BelongsToCompanyBranch, HasManager, InternalNumbering, OrderTrait;

    protected $fillable = [
        'date',
        'pay_type',
        'currency',
        'machinery_sale_request_id',
        'account_number',
        'account_date',
        'status',
        'creator_id',
        'company_branch_id',
    ];

   // protected $with = ['machines', 'contract'];

    function customer()
    {
        return $this->morphTo();
    }


    function machines()
    {
        return $this->morphToMany(Machinery::class, 'owner', 'machinery_shop_characteristic', 'machinery_id');
    }

    function operations()
    {
        return $this->morphMany(OperationCharacteristic::class, 'owner');
    }

    function saleRequest()
    {
        return $this->belongsTo(MachinerySaleRequest::class, 'machinery_sale_request_id');
    }

    function documents()
    {
        return $this->morphMany(OrderDocument::class, 'order');
    }

    function contract()
    {
        return $this->morphOne(SaleContract::class, 'owner');
    }

    function generateApplication($operationId)
    {
        $service = new SaleService([
            'operation_id' => $operationId
        ]);

        return $service->generateApplication($this);
    }

    function getContractUrl()
    {
        return $this->saleRequest->contract ? $this->saleRequest->contract->getOrderContractUrl($this): false;
    }
}
