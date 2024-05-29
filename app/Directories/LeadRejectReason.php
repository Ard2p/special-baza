<?php

namespace App\Directories;

use App\Overrides\Model;

class LeadRejectReason extends Model
{

    public $timestamps = false;

    protected $primaryKey = 'key';

    public $incrementing = false;

    protected $fillable = ['key'];

    protected $appends = ['name'];

    const REASON_NO_MACHINERIES = 'no_machineries';

    const REASON_NO_FREE_MACHINERIES = 'no_free_machineries';

    const REASON_NO_PAYMENT_TRANSFERRED = 'no_payment_transferred';

    const REASON_WRONG_REGION = 'wrong_region';

    //const REASON_TIMEOUT = 'timeout';
    const REASON_CONTRACTOR_NOT_FOUND = 'contractor_not_found';
    const REASON_BAD_PRICE = 'bad_price';
    const REASON_AVITO_CUSTOMER = 'avito_customer';
    const REASON_BAD_TIME = 'bad_time';
    const REASON_BAD_AVITO = 'avito';
    const REASON_BAD_IGNORE = 'ignore';
    const REASON_BAD_IMPOSSIBLE = 'impossible';

    const REASON_OTHER = 'other';

    const CONTRACT_FAIL = 'contract_fail';


    function getNameAttribute()
    {
        return trans('transbaza_statuses.proposal_reject_' . $this->key);
    }

    static function implodeInString()
    {
        $all = self::query()->pluck('key')->toArray();

        return implode(',', $all);
    }

}
