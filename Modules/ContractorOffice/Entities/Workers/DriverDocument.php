<?php

namespace Modules\ContractorOffice\Entities\Workers;

use Illuminate\Database\Eloquent\Model;
use Modules\ContractorOffice\Entities\CompanyWorker;

class DriverDocument extends Model
{

    protected $table = 'workers_driver_documents';

    protected $fillable = [
        'driving_licence_number',
        'driving_licence_expired_date',
        'driving_licence_place_of_issue',
        'driving_licence_scans',
        'machinery_licence_number',
        'machinery_licence_scans',
        'machinery_licence_place_of_issue',
        'machinery_licence_date_of_issue',
        'company_worker_id',
    ];

    protected $casts = [
        'driving_licence_scans' => 'array',
        'machinery_licence_scans' => 'array',
    ];

  //  protected $with = [
  //      'drivingCategories',
  //      'machineryCategories',
  //  ];

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

    function worker()
    {
        return $this->belongsTo(CompanyWorker::class, 'company_worker_id');
    }

    function drivingCategories()
    {
        return $this->hasMany(DrivingLicence::class, 'workers_driver_document_id');
    }

    function machineryCategories()
    {
        return $this->hasMany(MachineryLicence::class, 'workers_driver_document_id');
    }

    function generateScanImages()
    {
        processImages($this, $this->driving_licence_scans, $this->worker->getDefaultDir() . '/driving_licences', 'driving_licence', 'driving_licence_scans');
        processImages($this, $this->machinery_licence_scans, $this->worker->getDefaultDir() . '/machinery_licence', 'machinery_licence', 'machinery_licence_scans');
    }


}
