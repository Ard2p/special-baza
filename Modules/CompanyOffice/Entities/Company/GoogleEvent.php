<?php

namespace Modules\CompanyOffice\Entities\Company;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\CompanyOffice\Services\BelongsToCompanyBranch;

class GoogleEvent extends Model
{
    use BelongsToCompanyBranch;

    const TYPE_APPLICATION = 1;
    const TYPE_RENT = 2;
    const TYPE_SERVICE = 3;
    const TYPE_TASK = 4;

    protected $fillable = [
        'google_event_id',
        'type',
        'eventable_id',
        'eventable_type',
        'company_branch_id',
        'google_calendar_id',
    ];

    protected $hidden = ['created_at', 'updated_at'];

    protected $casts = [

    ];

    public function google_calendar(): BelongsTo
    {
        return $this->belongsTo(GoogleCalendar::class);
    }

    public function eventable()
    {
        return $this->morphTo();
    }
}
