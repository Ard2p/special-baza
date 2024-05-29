<?php

namespace Modules\CompanyOffice\Entities\Company;

use Illuminate\Database\Eloquent\Model;
use Modules\CompanyOffice\Services\BelongsToCompanyBranch;

class InsTariffSetting extends Model
{
    use BelongsToCompanyBranch;

    protected $fillable = [
        'market_price_min',
        'market_price_max',
        'rent_days_count',
        'repair_count',
        'one_compensation_percent',
        'all_compensations_percent',
        'franchise_total',
        'franchise_repair',
        'b2b_tariff_1_5',
        'b2b_tariff_5_21',
        'b2b_tariff_21_60',
        'b2b_tariff_60',
        'b2c_tariff_1_5',
        'b2c_tariff_5_21',
        'b2c_tariff_21_60',
        'b2c_tariff_60',
        'company_branch_id',
    ];

    protected $hidden = ['created_at', 'updated_at'];

    public function company_branch()
    {
        return $this->belongsTo(CompanyBranch::class);
    }

}
