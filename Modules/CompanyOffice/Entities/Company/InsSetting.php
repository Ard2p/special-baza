<?php

namespace Modules\CompanyOffice\Entities\Company;

use Illuminate\Database\Eloquent\Model;
use Modules\CompanyOffice\Services\BelongsToCompanyBranch;

class InsSetting extends Model
{
    use BelongsToCompanyBranch;

    protected $fillable = [
        'ins_tariff_id',
        'date_from',
        'date_to',
        'price',
        'active',
        'contract_number',
        'contract_date',
        'contract_status',
        'increase_rent_price',
        'company_branch_id',
    ];

    protected $hidden = ['created_at', 'updated_at'];

    protected $casts = [
        'active' => 'boolean',
        'increase_rent_price' => 'boolean',
        'date_from' => 'datetime:Y-m-d',
        'date_to' => 'datetime:Y-m-d',
        'contract_date' => 'datetime:Y-m-d',
    ];

    public function ins_tariff()
    {
        return $this->belongsTo(InsTariff::class);
    }

    public function company_branch()
    {
        return $this->belongsTo(CompanyBranch::class);
    }

    public function ins_setting_logs()
    {
        return $this->hasMany(InsSettingLog::class);
    }

}
