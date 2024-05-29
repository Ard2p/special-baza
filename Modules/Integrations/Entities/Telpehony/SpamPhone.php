<?php

namespace Modules\Integrations\Entities\Telpehony;

use App\Overrides\Model;
use Modules\CompanyOffice\Entities\Company;
use Modules\CompanyOffice\Services\BelongsToCompany;

class SpamPhone extends Model
{

    public $timestamps = false;
    use BelongsToCompany;

    protected $fillable = [
        'phone',
        'company_id'
    ];

    function company()
    {
        return $this->belongsTo(Company::class);
    }
}
