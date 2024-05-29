<?php

namespace Modules\CompanyOffice\Entities;

use Illuminate\Database\Eloquent\Model;
use Modules\CompanyOffice\Services\BelongsToCompanyBranch;

class Budget extends Model
{
    use  BelongsToCompanyBranch;

    protected $table = 'company_budgets';

    protected $fillable = [
        'company_branch_id',
        'year',
        'direction',
        'month',
        'type',
        'owner_type',
        'owner_id',
        'sum',
    ];

    function owner()
    {
        return $this->morphTo();
    }
}
