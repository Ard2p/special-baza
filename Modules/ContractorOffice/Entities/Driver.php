<?php

namespace Modules\ContractorOffice\Entities;

use App\Overrides\Model;
use Modules\CompanyOffice\Services\BelongsToCompanyBranch;

class Driver extends Model
{

    use BelongsToCompanyBranch;

    protected $fillable = [
        'full_name',
        'phone',
        'passport',
        'machinery_number',
        'brand',
        'company_branch_id',
    ];

    protected static function boot()
    {
        parent::boot();

    }
}
