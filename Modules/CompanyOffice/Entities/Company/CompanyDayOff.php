<?php

namespace Modules\CompanyOffice\Entities\Company;

use App\Overrides\Model;
use Modules\CompanyOffice\Services\BelongsToCompanyBranch;

class CompanyDayOff extends Model
{

    public $timestamps = false;

    use BelongsToCompanyBranch;

    protected $fillable = [
        'date',
        'company_branch_id',
        'name',
    ];


}
