<?php

namespace Modules\CompanyOffice\Entities\Company;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\CompanyOffice\Services\BelongsToCompanyBranch;

class GoogleCalendar extends Model
{
    use BelongsToCompanyBranch;

    const TYPE_APPLICATION = 1;
    const TYPE_RENT = 2;
    const TYPE_SERVICE = 3;
    const TYPE_TASK = 4;

    protected $fillable = [
        'google_id',
        'summary',
        'type',
        'google_api_setting_id',
        'company_branch_id',
    ];

    protected $hidden = ['created_at', 'updated_at'];

    protected $casts = [

    ];

    public function google_api_setting(): BelongsTo
    {
        return $this->belongsTo(GoogleApiSetting::class);
    }
}