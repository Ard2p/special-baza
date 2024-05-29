<?php

namespace Modules\Integrations\Entities\OneC;

use App\Overrides\Model;
use Modules\CompanyOffice\Services\BelongsToCompanyBranch;

class Connector extends Model
{

    protected $table = '1c_connectors';
    use BelongsToCompanyBranch;

    protected $fillable = [
        'onec_id',
        'company_branch_id'
    ];



}
