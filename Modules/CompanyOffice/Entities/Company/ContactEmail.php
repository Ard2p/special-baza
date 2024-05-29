<?php

namespace Modules\CompanyOffice\Entities\Company;

use App\Overrides\Model;
use App\User\IndividualRequisite;

class ContactEmail extends Model
{

    public $timestamps = null;

    protected $fillable = [
        'email',
        'individual_requisite_id',
    ];


    function setEmailAttribute($val)
    {
        $this->attributes['email'] = strtolower($val);
    }


    function contact()
    {
        return $this->belongsTo(IndividualRequisite::class, 'individual_requisite_id');
    }
}
