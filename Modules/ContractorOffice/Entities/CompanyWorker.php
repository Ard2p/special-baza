<?php

namespace Modules\ContractorOffice\Entities;

use App\Machinery;
use App\User;
use Carbon\Carbon;
use App\Overrides\Model;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Modules\CompanyOffice\Services\BelongsToCompanyBranch;
use Modules\CompanyOffice\Services\HasContacts;
use Modules\ContractorOffice\Entities\System\DrivingCategory;
use Modules\ContractorOffice\Entities\Vehicle\TechnicalWork;
use Modules\ContractorOffice\Entities\Workers\DriverDocument;
use Modules\Orders\Entities\Order;
use Modules\Orders\Entities\OrderComponent;

class CompanyWorker extends Model
{

    use BelongsToCompanyBranch, HasContacts;

    protected $fillable = [
        'photos',
        'company_branch_id',
        'type',
        'passport_number',
        'passport_scans',
        'passport_place_of_issue',
        'passport_date_of_issue',
    ];

    protected $appends = ['contact', 'name'];

     protected $with = ['driverDocument'];

    protected $casts = [
        'photos' => 'array',
        'passport_scans' => 'array'
    ];

    protected static function boot()
    {
        parent::boot();

        self::created(function (self $model) {
            $model->generateScanImages();
        });

        self::updated(function (self $model) {
            $model->generateScanImages();
        });
    }


    const TYPE_DRIVER = 'driver';
    const TYPE_MECHANIC = 'mechanic';

    function user()
    {
        return $this->belongsTo(User::class);
    }

    function getContactAttribute()
    {
        return $this->contacts->first();
    }

    function getNameAttribute()
    {
        return $this->contact ? $this->contact->full_name : '';
    }

    function orderComponents()
    {
        return $this->hasMany(OrderComponent::class);
    }

    function technicalWorks()
    {
        return $this->belongsToMany(TechnicalWork::class, 'technical_works_mechanics');
    }

    function scopeCheckAvailable($q, Carbon $dateFrom, Carbon $dateTo, $type = self::TYPE_DRIVER)
    {
        return $type === self::TYPE_DRIVER ? $q->whereDoesntHave('orderComponents', function ($q) use($dateFrom, $dateTo) {
            $q->forPeriod($dateFrom, $dateTo);
            $q->whereNotIn('status',[ Order::STATUS_ACCEPT, Order::STATUS_DONE, Order::STATUS_REJECT]);
        })
            : $q->whereDoesntHave('technicalWork', function ($q) use($dateFrom, $dateTo) {
                $q->whereHas('periods', function ($q) use($dateFrom, $dateTo) {
                   $q->forPeriod($dateFrom, $dateTo);
                });
            });
    }

    function getDefaultDir()
    {
        return "companies/{$this->company_branch->company_id}/branch-{$this->company_branch->id}/workers/{$this->id}";
    }

    function driverDocument()
    {
        return $this->hasOne(DriverDocument::class);
    }


    function machinery()
    {
        return $this->belongsToMany(Machinery::class, 'company_workers_machinery');
    }

    function generateScanImages()
    {
        processImages($this, $this->photos, $this->getDefaultDir(), 'photo', 'photos');
        processImages($this, $this->passport_scans, $this->getDefaultDir() . '/passport', 'passport_scan', 'passport_scans');
    }
}
