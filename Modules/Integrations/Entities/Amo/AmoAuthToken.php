<?php

namespace Modules\Integrations\Entities\Amo;

use App\User;
use Illuminate\Database\Eloquent\Model;
use Modules\CompanyOffice\Services\BelongsToCompanyBranch;

class AmoAuthToken extends Model
{

    use BelongsToCompanyBranch;

    protected $table = 'integrations_amo_auth_tokens';

    protected $fillable = [
        'access_token',
        'refresh_token',
        'base_domain',
        'expires_at',
        'company_branch_id'
    ];

    protected $dates = ['expires_at'];

}
