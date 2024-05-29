<?php

namespace Modules\CompanyOffice\Entities\Company;

use Illuminate\Database\Eloquent\Model;
use Modules\CompanyOffice\Services\BelongsToCompanyBranch;

class ClientBankSetting extends Model
{
    use BelongsToCompanyBranch;

    protected $fillable = [
        'name',
        'parameters',
        'company_branch_id',
    ];

    protected $hidden = ['created_at', 'updated_at'];

    protected $casts = [
        'parameters' => 'json',
    ];

    public function company_branch()
    {
        return $this->belongsTo(CompanyBranch::class);
    }

}
