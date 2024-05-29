<?php

namespace Modules\Dispatcher\Entities\Directories;

use App\City;
use App\Machinery;
use App\Support\Region;
use App\User;
use App\User\EntityRequisite;
use App\User\IndividualRequisite;
use Illuminate\Database\Eloquent\Builder;
use App\Overrides\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\ValidationException;
use Modules\CompanyOffice\Entities\Company\CompanyBranch;
use Modules\CompanyOffice\Entities\Company\Contact;
use Modules\CompanyOffice\Services\BelongsToCompanyBranch;
use Modules\CompanyOffice\Services\ContactsService;
use Modules\CompanyOffice\Services\HasContacts;
use Modules\CorpCustomer\Entities\InternationalLegalDetails;
use Modules\Dispatcher\Entities\Customer\CustomerContract;
use Modules\Dispatcher\Entities\DispatcherOrder;
use Modules\Dispatcher\Entities\Lead;
use Modules\Orders\Entities\Order;
use Modules\Orders\Entities\OrderComponent;
use Modules\RestApi\Entities\Domain;
use function Clue\StreamFilter\fun;

class Contractor extends Model
{

    use SoftDeletes, BelongsToCompanyBranch, HasContacts;

    protected $table = 'dispatcher_contractors';

    protected $fillable = [
        'company_name',
        'address',
        'contact_person',
        'last_application_id',
        'phone',
        'region_id',
        'city_id',
        'domain_id',
        'company_branch_id',
        'creator_id'
    ];

    function contract()
    {
        return $this->morphOne(CustomerContract::class, 'customer');
    }

    function machineries()
    {
        return $this->morphMany(Machinery::class, 'sub_owner');
    }

    function domain()
    {
        return $this->belongsTo(Domain::class);
    }


    function region()
    {
        return $this->belongsTo(Region::class);
    }

    function city()
    {
        return $this->belongsTo(City::class);
    }

    function user()
    {
        return $this->belongsTo(User::class, 'creator_id');
    }

    function orders()
    {
        return Order::query()->whereHas('components', function (Builder $q) {
            $q->whereHasMorph('worker', [Machinery::class], function (Builder $q) {
                $q->where('sub_owner_id', $this->id)
                ->where('sub_owner_type', self::class);
            });
        });
    }

    function orderComponents()
    {
        return $this->hasManyThrough(OrderComponent::class, Machinery::class, 'sub_owner_id', 'worker_id')
            ->where('worker_type',  Machinery::class)
            ->where('sub_owner_type',  static::class);

    }


    function entity_requisites()
    {
        return $this->morphOne( EntityRequisite::class, 'requisite');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\MorphOne
     */
    public function international_legal_requisites()
    {
        return $this->morphOne(InternationalLegalDetails::class, 'requisite');
    }

    function individual_requisites()
    {
        return $this->morphOne(IndividualRequisite::class, 'requisite');
    }


    function getLegalRequisitesAttribute()
    {
        return $this->domain->alias === 'ru' ? $this->entity_requisites : $this->international_legal_requisites;
    }


    function hasRequisite()
    {
        return $this->entity_requisites()->exists() || $this->individual_requisites()->exists();
    }

    function getTypeAttribute()
    {
        return $this->hasRequisite()
            ? ($this->entity_requisites()->exists() ? trans('tb_requisites.legal') : trans('tb_requisites.individual'))
            : '';
    }


    function scopeForDomain($q, $domain = null)
    {
        $domain = $domain ?: request()->header('domain');

        if (!$domain) {
            return $q;
        }
        return $q->whereHas('region', function ($q) use ($domain) {
            $q->whereHas('country', function ($q) use ($domain) {
                $q->whereHas('domain', function ($q) use ($domain) {
                    $q->whereAlias($domain);
                });
            });
        });
    }

    function getDomainAttribute()
    {
        return Domain::query()->whereHas('countries', function ($q){
           $q->whereHas('regions', function ($q){
              $q->where('regions.id', $this->region_id);
           });
        })->first();
    }

    function getDebt()
    {
        $sum = $this->orderComponents()->get()->sum('contractor_sum');
        $paid =$this->orderComponents()->get()->sum('contractor_paid_sum');

        return $sum - $paid;
    }


    function addLegalRequisites($data)
    {
        $requisite = $this->domain->alias === 'ru'
            ? ($this->entity_requisites ? $this->entity_requisites->update($data) : $this->entity_requisites()->save((new EntityRequisite($data))))
            : ($this->international_legal_requisites ? $this->international_legal_requisites->update($data) : $this->international_legal_requisites()->save(( new InternationalLegalDetails($data))));

        return $requisite;
    }

    function addIndividualRequisites($data)
    {
        if(!$data['type']) {
            $data['type'] = IndividualRequisite::TYPE_ENTREPRENEUR;
        }
        if($data['type'] === IndividualRequisite::TYPE_PERSON) {
            unset(
                $data['inn'],
                $data['kp'],
                $data['bank'],
                $data['bik'],
                $data['ks'],
                $data['ogrnip_date'],
                $data['ogrnip'],
                $data['signatory_name'],
                $data['signatory_short'],
                $data['signatory_genitive'],
                $data['rs']
            );
        }else {
            unset(
                /* $data['firstname'],
                 $data['middlename'],
                 $data['surname'],*/
                $data['gender'],
                $data['birth_date'],
                $data['passport_number'],
                $data['passport_date'],
                $data['issued_by']
            );
        }
        $requisite = new IndividualRequisite($data);

        $this->individual_requisites ?  $this->individual_requisites->update($data) : $this->individual_requisites()->save($requisite);

        return $requisite;
    }

    function addContacts($contacts)
    {
        $contactService = new ContactsService($this->company_branch);

        $allIds = $this->contacts->pluck('id')->toArray();

        foreach ($contacts as $contact) {

            if (!empty($contact['id'])) {
                $contactService->updateContact($contact['id'], $contact);
                $key = array_search($contact['id'], $allIds);
                if ($key !== false) {
                    unset($allIds[$key]);
                }
            } else {
                $contactService->createContact($contact, $this);
            }
        }

        Contact::query()->forBranch($this->company_branch->id)->whereIn('id', $allIds)->delete();
    }

    function generateContract($requisite = null)
    {
        $branchSettings = $this->company_branch->getSettings();

        $this->contract =
            $this->contract
                ?: CustomerContract::generateContract($this, null, null);
        if($requisite) {
            $this->contract->requisite()->associate($requisite);
            $this->contract->save();
        }
        return $this->contract;
    }

    function lastApplicationId()
    {
        return OrderComponent::query()->whereHasMorph('worker', [static::class], function ($q) {
            $q->where('sub_owner_id', $this->id);

        })->max('contractor_application_id');

    }


    function getAndIncrementApplicationId()
    {
        $lock = Cache::lock("lock_contractor_application_{$this->id}", 10);

        if ($lock->get()) {

            $this->increment('last_application_id');

            $lock->release();
        } else {
            $error = ValidationException::withMessages([
                'errors' => ['Генерация приложения недостпна в данный момент, попройте через несколько секунд.']
            ]);

            throw $error;
        }


        return $this->last_application_id;
    }

    function getRequisitesAttribute()
    {
        return $this->entity_requisites ?: $this->individual_requisites;
    }

    public function vehicles()
    {
        return $this->morphMany(Machinery::class,'sub_owner');
    }

}
