<?php

namespace Modules\CompanyOffice\Entities\Company;

use Illuminate\Database\Eloquent\Model;
use Modules\CompanyOffice\Services\BelongsToCompanyBranch;

class InsSettingLog extends Model
{
    protected $fillable = [
        'inc_setting_id',
        'ins_tariff_id',
        'date_from',
        'date_to',
        'price',
        'type',
    ];

    public function ins_tariff()
    {
        return $this->belongsTo(InsTariff::class);
    }
    public function ins_setting()
    {
        return $this->belongsTo(InsSetting::class);
    }

}
