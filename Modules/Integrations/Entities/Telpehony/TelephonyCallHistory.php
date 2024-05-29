<?php

namespace Modules\Integrations\Entities\Telpehony;

use App\User;
use Illuminate\Database\Eloquent\Builder;
use App\Overrides\Model;
use Modules\CompanyOffice\Entities\Company\ContactPhone;
use Modules\CompanyOffice\Services\BelongsToCompany;
use Modules\CompanyOffice\Services\BelongsToCompanyBranch;
use Modules\Dispatcher\Entities\Customer;
use Modules\Dispatcher\Entities\Lead;
use Modules\Orders\Entities\Order;
use function Clue\StreamFilter\fun;

class TelephonyCallHistory extends Model
{

    use BelongsToCompany;

    protected $fillable = [
        'phone',
        'manager_phone',
        'link',
        'call_id',
        'status',
        'raw_data',
        'important',
        'bind_type',
        'bind_id',
        'listened',
        'is_hidden',
        'company_id',
    ];

    protected $casts = [
        'raw_data' => 'object',
        'important' => 'boolean',
        'listened' => 'boolean',
        'is_hidden' => 'boolean',
    ];

    protected $appends = [
        'has_orders'
    ];

    function owner()
    {
        return $this->morphTo();
    }

    function bind()
    {
        return $this->morphTo();
    }

    function spamPhone()
    {
        return $this->hasOne(SpamPhone::class, 'phone', 'phone');
    }

    function getCustomers($branch_id = null)
    {
        $query = Customer::query()->with('contacts')->forCompany($this->company_id);

        if ($branch_id) {
            $query->forBranch($branch_id);
        }
        $query->where(function ($query) {
            $query->whereHas('contacts', function ($q) {
                $q->whereHas('phones', function ($q) {
                    $q->where('phone', $this->phone);
                });
            })->orWhere('phone', $this->phone);
        });

        return $query->get();

    }

    function scopeUnprocessed($q)
    {
        return $q->where('listened', false)
            ->whereNull('bind_id');
    }

    function scopeIncoming(Builder $q)
    {
        return $q->where('raw_data', 'like', "%\"in\"%");;
    }

    function scopeForCustomer($q, Customer $customer)
    {
        $phones = [];
        foreach ($customer->contacts as $contact) {
            foreach ($contact->phones as $phone) {
                $phones[] = $phone->phone;
            }
        }
        $phones[] = $customer->phone;

        return $q->whereIn('phone', $phones);
    }

    function contactPhone()
    {
        return $this->hasOne(ContactPhone::class, 'phone', 'phone');
    }

    function scopeCustomerExists(Builder $q)
    {
        return $q->whereHas('contactPhone', function ($q) {
            $q->whereHas('contact', function ($q) {
               $q->forBranch();
            });
        });
    }

    function scopeNewCustomer(Builder $q)
    {
        return $q->whereDoesntHave('contactPhone', function ($q) {
            $q->whereHas('contact', function ($q) {
                $q->forBranch();
            });
        });
    }

    function scopeSpam($q)
    {
        return $q->whereHas('spamPhone');
    }

    function scopeNoSpam($q)
    {
        return $q->whereDoesntHave('spamPhone');
    }

    function getHasOrdersAttribute()
    {
        return self::query()->forCompany()
            ->wherePhone($this->phone)
            ->where(function (Builder $q) {

                $q->whereHasMorph('bind', [Order::class], function (Builder $q) {
                    $q->whereStatus(Order::STATUS_ACCEPT);
                });
                $q->orWhereHasMorph('bind', [Lead::class], function (Builder $q) {
                    $q->whereStatus(Lead::STATUS_OPEN);
                });
                /*$q->orWhere(function (Builder $q) {
                   $q->whereHasMorph('owner', Customer::class, function ($q) {

                   });
                });*/
            })
            ->whereNotNull('bind_type')->exists();
    }

    function scopeForManager($q, User $user)
    {
        $phones = [];
        $user->contacts->each(function ($contact) use (&$phones) {
            $phones[] = $contact->pluck('phone');
        });
        $phones[] = $user->phone;

        return $q->whereIn('manager_phone', $phones);
    }


    function setPhoneAttribute($val)
    {
        $this->attributes['phone'] = trimPhone($val);
    }


    static function createOrUpdate($account, $data)
    {
        $existsCall = $account->calls_history()->where('call_id', $data['callid'])->first();

        $fields = [
            'phone' => $data['phone'],
            'call_id' => $data['callid'],
            'raw_data' => $data,
        ];
        if (!empty($data['link'])) {
            $fields['link'] = $data['link'];
        }
        if (!empty($data['status'])) {
            $fields['status'] = $data['status'];
        }
        if (!empty($data['diversion'])) {
            $fields['manager_phone'] = trimPhone($data['diversion']);
        }
        if ($existsCall) {
            $existsCall->timestamps = false;
            $existsCall->created_at = now($account->company->branches->first()->timezone);
            $existsCall->updated_at = now($account->company->branches->first()->timezone);
            $existsCall->fill($fields);
            $existsCall->save();

        } else {
            $call = new TelephonyCallHistory($fields);
            $call->timestamps = false;
            $call->created_at = now($account->company->branches->first()->timezone);
            $call->updated_at = now($account->company->branches->first()->timezone);
            $call->company_id = $account->company_id;

            $existsCall = $account->calls_history()->save($call);
        }
        return $existsCall;
    }
}
