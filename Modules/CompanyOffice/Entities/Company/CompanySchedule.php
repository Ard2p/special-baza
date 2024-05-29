<?php

namespace Modules\CompanyOffice\Entities\Company;

use Carbon\Carbon;
use App\Overrides\Model;
use Modules\CompanyOffice\Services\BelongsToCompanyBranch;

class CompanySchedule extends Model
{

    protected $table =  'company_schedule';

    use BelongsToCompanyBranch;

    protected $fillable = [
        'day_of_week',
        'day_off',
        'company_branch_id',
    ];

   // protected $with = ['workHours'];

    protected $casts =[
        'day_off' => 'boolean',
    ];

    protected $appends = ['day_name'];

    public $timestamps = false;

    function workHours()
    {
        return $this->hasMany(CompanyWorkHours::class, 'company_schedule_id');
    }

    function getDayNameAttribute()
    {

        return Carbon::now()->startOfWeek()->addDays($this->day_of_week)->locale(\App::getLocale())->dayName;
    }

    function getMinHourAttribute()
    {
        return Carbon::parse($this->workHours()->min('time_from'))->format('H:i');
    }

    function getMaxHourAttribute()
    {
        return $this->workHours()->max('time_to');
    }
}
