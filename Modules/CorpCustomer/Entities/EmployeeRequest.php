<?php

namespace Modules\CorpCustomer\Entities;

use App\User;
use App\Overrides\Model;

class EmployeeRequest extends Model
{
    protected $fillable = [
        'corp_company_id',
        'user_id',
        'position',
        'link',
        'status'
    ];

    function company()
    {
        return $this->belongsTo(CorpCompany::class, 'corp_company_id');
    }

    function user()
    {
        return $this->belongsTo(User::class);
    }


}
