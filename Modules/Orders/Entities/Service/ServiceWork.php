<?php

namespace Modules\Orders\Entities\Service;

use App\Overrides\Model;
use Modules\CompanyOffice\Services\BelongsToCompanyBranch;

class ServiceWork extends Model
{

    use BelongsToCompanyBranch;

    protected $table = 'service_works';
    public $timestamps = false;

    protected $fillable = [
        'name',
        'price',
        'company_branch_id',
    ];


    function setPriceAttribute($val)
    {
        return round($val * 100);
    }


    function getPriceAttribute($val)
    {
        return $val / 100;
    }

    function serviceOrders()
    {
        return $this->belongsToMany(ServiceCenter::class, 'service_works_center')->withPivot([
            'price',
            'count'
        ]);
    }
}
