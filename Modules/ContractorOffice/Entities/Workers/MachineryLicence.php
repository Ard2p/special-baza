<?php

namespace Modules\ContractorOffice\Entities\Workers;

use Illuminate\Database\Eloquent\Model;
use Modules\ContractorOffice\Entities\System\DrivingCategory;

class MachineryLicence extends Model
{

    protected $table = 'workers_machinery_licences';
    public $timestamps = false;
    protected $fillable = [
        'driving_category_id',
        'date_of_issue',
        'expired_date',
        'experience_start',
        'workers_driver_document_id',
    ];


    function category()
    {
        return $this->belongsTo(DrivingCategory::class)->where('type', '=', DrivingCategory::TYPE_MACHINERY_LICENCE);
    }

    function document()
    {
        return $this->belongsTo(DriverDocument::class, 'workers_driver_document_id');
    }
}
