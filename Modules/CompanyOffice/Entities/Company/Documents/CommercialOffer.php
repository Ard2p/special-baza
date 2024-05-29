<?php

namespace Modules\CompanyOffice\Entities\Company\Documents;

use Illuminate\Database\Eloquent\Model;
use Modules\CompanyOffice\Services\BelongsToCompanyBranch;

class CommercialOffer extends Model
{
    protected $table = 'company_commercial_offers';

    use BelongsToCompanyBranch;

    protected $fillable = [
        'name',
        'number',
        'url',
        'default_text',
        'company_branch_id'
    ];
}
