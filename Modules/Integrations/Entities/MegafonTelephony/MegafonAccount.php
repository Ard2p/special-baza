<?php

namespace Modules\Integrations\Entities\MegafonTelephony;

use Illuminate\Database\Eloquent\Model;
use Modules\CompanyOffice\Services\BelongsToCompany;
use Modules\Integrations\Entities\Telpehony\TelephonyCallHistory;

class MegafonAccount extends Model
{

    use BelongsToCompany;

    protected $table = 'telephony_megafon_accounts';

    protected $fillable = [
        'company_id',
        'token'
    ];

    protected $appends = ['postback_url'];

    function getPostbackUrlAttribute()
    {
       return route('megafon_callback');
    }

    function calls_history()
    {
        return $this->morphMany(TelephonyCallHistory::class, 'owner');
    }
}
