<?php

namespace Modules\CompanyOffice\Entities\Company;

use App\Overrides\Model;
use App\User\IndividualRequisite;

class ContactPhone extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'phone',
        'individual_requisite_id'
    ];

    function setPhoneAttribute($val)
    {
        $this->attributes['phone'] = trimPhone($val);
    }

    function contact()
    {
        return $this->belongsTo(IndividualRequisite::class, 'individual_requisite_id');
    }
}
