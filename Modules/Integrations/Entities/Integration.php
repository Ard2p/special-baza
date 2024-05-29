<?php

namespace Modules\Integrations\Entities;

use App\User;
use Illuminate\Database\Eloquent\Model;
use Modules\CompanyOffice\Entities\Company\CompanyBranch;

class Integration extends Model
{
    protected $fillable = [
        'name',
        'parent_id',
        'event_back_url'
    ];



    function company_branches()
    {
        return $this->belongsToMany(CompanyBranch::class, 'integrations_user')->withPivot('native_id');
    }
}
