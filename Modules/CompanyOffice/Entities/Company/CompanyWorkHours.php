<?php

namespace Modules\CompanyOffice\Entities\Company;

use Carbon\Carbon;
use App\Overrides\Model;
use Modules\CompanyOffice\Services\BelongsToCompanyBranch;

class CompanyWorkHours extends Model
{
    protected $table = 'company_work_hours';

    protected $fillable = [
        'time_from',
        'time_to',
        'company_schedule_id'

    ];

    public $timestamps = false;

    function schedule()
    {
        return $this->belongsTo(CompanySchedule::class, 'company_schedule_id');
    }

    function getTimeFromAttribute($val)
    {
        return $val ? Carbon::parse($val)->format('H:i'): '';
    }

    function getTimeToAttribute($val)
    {
        return $val ? Carbon::parse($val)->format('H:i'): '';
    }
}
