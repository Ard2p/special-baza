<?php

namespace App\Http\Controllers\Avito\Models;

use App\Overrides\Model;
use Modules\CompanyOffice\Services\BelongsToCompanyBranch;

class AvitoStat extends Model
{
    use BelongsToCompanyBranch;

    protected $fillable = [
        'orders_count',
        'company_branch_id'
    ];

    protected $casts =[
    ];
}
