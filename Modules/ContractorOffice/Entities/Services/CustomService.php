<?php

namespace Modules\ContractorOffice\Entities\Services;

use App\Directories\Unit;
use App\Machines\Type;
use Illuminate\Database\Eloquent\Model;
use Modules\CompanyOffice\Services\BelongsToCompanyBranch;
use Modules\Orders\Entities\OrderComponentService;
use Modules\Orders\Entities\Service\ServiceCenter;

class CustomService extends Model
{
    use BelongsToCompanyBranch;

    protected $fillable = [
        'name',
        'price',
        'vendor_code',
        'is_pledge',
        'is_for_service',
        'company_branch_id',
        'price_cash',
        'price_cashless',
        'price_cashless_vat',
        'unit_id',
        'value_added',
        'value_added_cashless',
        'value_added_cashless_vat',
    ];

    protected $casts = [
        'is_pledge' => 'boolean',
        'is_for_service' => 'boolean',
    ];

    function categories()
    {
        return $this->belongsToMany(Type::class, 'custom_services_categories', 'custom_service_id', 'category_id');
    }

    function unit()
    {
        return $this->belongsTo(Unit::class);
    }

    function serviceComponent()
    {
        return $this->hasMany(OrderComponentService::class, 'custom_service_id');
    }

    function serviceCenters()
    {
        return $this->belongsToMany(ServiceCenter::class, 'service_center_custom_services')->withPivot([
            'price',
            'count',
            'comment',
        ]);
    }

}
