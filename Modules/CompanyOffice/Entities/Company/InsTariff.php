<?php

namespace Modules\CompanyOffice\Entities\Company;

use Illuminate\Database\Eloquent\Model;
use Modules\CompanyOffice\Services\BelongsToCompanyBranch;

class InsTariff extends Model
{
    protected $fillable = [
        'name',
        'status',

    ];

    protected $casts = [
      'status' => 'boolean'
    ];

    public function ins_settings()
    {
        return $this->hasMany(InsSetting::class);
    }

    public function ins_setting_logs()
    {
        return $this->belongsTo(InsSettingLog::class);
    }

}
