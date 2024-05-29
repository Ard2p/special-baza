<?php

namespace Modules\Dispatcher\Entities;

use App\Machines\Type;
use App\User;
use Illuminate\Database\Eloquent\Model;
use Modules\CompanyOffice\Services\BelongsToCompanyBranch;
use Modules\CompanyOffice\Services\HasManager;
use OwenIt\Auditing\Auditable;


class LeadOfferPosition extends Model implements \OwenIt\Auditing\Contracts\Auditable
{

    use Auditable, BelongsToCompanyBranch, HasManager;
    public $timestamps = false;

    protected $appends = ['sum'];

    protected $fillable = [
        'lead_offer_id',
        'creator_id',
        'company_branch_id',
        'category_id',
        'amount',
        'value_added',
    ];

    function offer()
    {
        return $this->belongsTo(LeadOffer::class, 'lead_offer_id');
    }

    function category()
    {
        return $this->belongsTo(Type::class,'category_id');
    }


    function worker()
    {
        return $this->morphTo();
    }

    function getSumAttribute()
    {
        return $this->value_added + $this->amount;
    }
}
