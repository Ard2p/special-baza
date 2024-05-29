<?php

namespace Modules\ContractorOffice\Entities\Vehicle;

use App\City;
use App\Machinery;
use App\Support\Region;
use App\User;
use Illuminate\Database\Eloquent\Model;
use Modules\CompanyOffice\Services\BelongsToCompanyBranch;
use Modules\ContractorOffice\Entities\CompanyWorker;
use Modules\RestApi\Traits\HasCoordinates;

class MachineryBase extends Model
{

    use BelongsToCompanyBranch, HasCoordinates;

    public $timestamps = false;

    protected $fillable = [
        'name',
        'address',
        'coordinates',
        'company_branch_id',
        'company_worker_id',
        'kpp',
        'city_id',
        'region_id',
        'insurance_premium',
        'cancel_after',
        'payment_percent',
    ];

    protected static function boot()
    {
        parent::boot();

        self::updated(function (self $base) {
            $base->machineries->each(function ($machine) use ($base) {
               $machine->update([
                 'address' => $base->address,
                 'coordinates' => $base->coordinates,
                 'city_id' => $base->city_id,
                 'region_id' => $base->region_id,
               ]);
            });
        });
    }


    function machineries()
    {
        return $this->hasMany(Machinery::class, 'base_id');
    }

    function region()
    {
        return $this->belongsTo(Region::class);
    }

    function city()
    {
        return $this->belongsTo(City::class);
    }

    function companyWorker()
    {
        return $this->belongsTo(CompanyWorker::class, 'company_worker_id');
    }
}
