<?php

namespace Modules\CompanyOffice\Entities\Company;

use App\Overrides\Model;
use Modules\CompanyOffice\Services\BelongsToCompanyBranch;
use Modules\Dispatcher\Entities\Customer;

class Contact extends Model
{

    public $timestamps = false;
    use BelongsToCompanyBranch;

    protected $fillable = [
        'contact_person',
        //'email',
        //'phone',
        'position',
        'company_branch_id',
    ];

    protected $with = ['phones', 'emails'];

    function setEmailAttribute($val)
    {
        $this->attributes['email'] = strtolower($val);
    }


    function owner()
    {
        return $this->morphTo();
    }

    function phones()
    {
        return $this->hasMany(ContactPhone::class);
    }

    function emails()
    {
        return $this->hasMany(ContactEmail::class);
    }

    function scopeFindByContactPerson($q, $contact_person)
    {
        return $q->where(function ($q) use ($contact_person) {
            $q->where('contacts.contact_person', 'like', "%{$contact_person}%")
                ->orWhereHasMorph('owner', [Customer::class], function ($q) use ($contact_person) {
                    $q->where('dispatcher_customers.contact_person', 'like', "%{$contact_person}%");
                });
        });

    }

    function scopeFindByEmail($q, $email)
    {
        return $q->where(function ($q) use ($email) {
            $q->where('contacts.email', 'like', "%{$email}%")
                ->orWhereHasMorph('owner', [Customer::class], function ($q) use ($email) {
                    $q->where('dispatcher_customers.email', 'like', "%{$email}%");
                });
        });

    }

}
