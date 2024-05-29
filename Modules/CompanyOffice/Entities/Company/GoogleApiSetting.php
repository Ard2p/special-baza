<?php

namespace Modules\CompanyOffice\Entities\Company;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\CompanyOffice\Services\BelongsToCompanyBranch;

class GoogleApiSetting extends Model
{
    use BelongsToCompanyBranch;

    protected $fillable = [
        'access_token',
        'refresh_token',
        'expires_in',
        'created',
        'scope',
        'company_branch_id'
    ];

    protected $hidden = ['created_at', 'updated_at'];

    protected $casts = [

    ];

    public function calendars(): HasMany
    {
        return $this->hasMany(GoogleCalendar::class);
    }
}
