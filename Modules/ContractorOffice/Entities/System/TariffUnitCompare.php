<?php

namespace Modules\ContractorOffice\Entities\System;

use Illuminate\Database\Eloquent\Model;
use Modules\CompanyOffice\Services\BelongsToCompanyBranch;

class TariffUnitCompare extends Model
{

    use BelongsToCompanyBranch;

    public $timestamps = false;

    protected $fillable = [
        'name',
        'type',
        'amount',
        'is_month',
        'company_branch_id',
    ];

    protected $casts = ['is_month' => 'boolean'];
}
