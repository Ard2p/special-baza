<?php

namespace Modules\Dispatcher\Entities;

use App\City;
use App\Helpers\RequestHelper;
use App\Overrides\Model;
use App\Service\Scoring\Models\Scoring;
use App\Support\Region;
use App\User;
use App\User\EntityRequisite;
use App\User\IndividualRequisite;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;
use Modules\CompanyOffice\Entities\Company;
use Modules\CompanyOffice\Entities\Company\CompanyBranch;
use Modules\CompanyOffice\Entities\CompanyTag;
use Modules\CompanyOffice\Entities\Employees\EmployeeTask;
use Modules\CompanyOffice\Services\BelongsToCompanyBranch;
use Modules\CompanyOffice\Services\HasContacts;
use Modules\CompanyOffice\Services\HasManager;
use Modules\CompanyOffice\Services\InternalNumbering;
use Modules\ContractorOffice\Entities\Vehicle\Shop\MachinerySale;
use Modules\CorpCustomer\Entities\InternationalLegalDetails;
use Modules\Dispatcher\Entities\Customer\CustomerContract;
use Modules\Orders\Entities\Order;
use Modules\Orders\Entities\OrderComponent;
use Modules\Orders\Entities\Service\ServiceCenter;
use Modules\PartsWarehouse\Entities\Shop\Parts\PartsSale;
use Modules\RestApi\Entities\Domain;

class Customer extends Model
{

    use BelongsToCompanyBranch, HasManager, HasContacts, InternalNumbering;

    protected $table = 'dispatcher_customers';

    protected $fillable = [
        'name',
        'phone',
        'email',
        'company_name',
        'contact_person',
        'contact_position',
        'address',
        'region_id',
        'domain_id',
        'city_id',
        'in_black_list',
        'creator_id',
        'company_branch_id',
        'options',
        'last_application_id',
        'source',
        'channel',
    ];

    protected $casts = [
        'in_black_list' => 'boolean',
        'options' => 'object'
    ];

    // protected $with = ['domain', 'individual_requisites', 'contacts'];

    protected $appends = ['legal_requisites', 'has_requisites'];

    protected static function boot()
    {
        parent::boot();
        self::created(function (self $model) {
            $model->setInternalNumber();
            Cache::forget("{$model->company_branch_id}_customers");
        });
        self::updated(function (self $model) {
            Cache::forget("{$model->company_branch_id}_customers");
        });
        self::deleted(function (self $model) {
            Cache::forget("{$model->company_branch_id}_customers");
            $model->entity_requisites()->delete();
            $model->international_legal_requisites()->delete();
            $model->manager_notes()->delete();
            $model->contacts()->delete();
            $model->individual_requisites()->delete();
        });
    }


    function setEmailAttribute($val)
    {
        $this->attributes['email'] = strtolower($val);

    }


    function tags()
    {
        return $this->morphToMany(CompanyTag::class, 'taggable', 'company_taggables');
    }

    function scorings()
    {
        return $this->hasMany(Scoring::class);
    }

    function scoring()
    {
        return $this->hasOne(Scoring::class)->latestOfMany();
    }

    function city()
    {
        return $this->belongsTo(City::class);
    }

    function region()
    {
        return $this->belongsTo(Region::class);
    }

    function tasks()
    {
        return $this->morphToMany(EmployeeTask::class, 'bind',
            'employee_tasks_binds',
            'bind_id',
            'employee_task_id',
            'id',
            'id');
    }

    function domain()
    {
        return $this->belongsTo(Domain::class);
    }

    function user()
    {
        return $this->belongsTo(User::class, 'creator_id');
    }


    function leads()
    {
        return $this->morphMany(Lead::class, 'customer');
    }

    function orders()
    {
        return $this->morphMany(Order::class, 'customer');
    }

    function manager_notes()
    {
        return $this->morphMany(ManagerNote::class, 'owner');
    }

    function entity_requisites()
    {
        return $this->morphOne(EntityRequisite::class, 'requisite');
    }

    function corporate_user()
    {
        return $this->belongsTo(User::class, 'corporate_user_id');
    }

    function getLegalRequisitesAttribute()
    {
        return $this->domain->alias === 'ru'
            ? $this->entity_requisites
            : $this->international_legal_requisites;
    }

    function getRequisites()
    {
        return $this->legal_requisites
            ?: $this->individual_requisites;
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

    function hasRequisite()
    {
        return $this->entity_requisites || $this->individual_requisites || $this->international_legal_requisites;
    }

    function getTypeAttribute()
    {
        return $this->hasRequisite()
            ? (($this->entity_requisites()->exists() || $this->international_legal_requisites()->exists())
                ? trans('tb_requisites.legal')
                : trans('tb_requisites.individual'))
            : '';
    }

    function getHasRequisitesAttribute()
    {
        return $this->hasRequisite();
    }


    function calculateDebt()
    {
        $invoices = DispatcherInvoice::query()->whereHasMorph('owner', [Order::class], function ($q) {
            $q->whereHas('leads', function ($q) {
                $q->whereCustomerId($this->id);
                $q->whereCustomerType(self::class);
            });
        })->get();


        $debt = 0;

        $invoices->each(function ($invoice) use (
            &
            $debt
        ) {
            $debt += ($invoice->sum - $invoice->paid);
        });

        return $debt;

    }

    public function invoices()
    {
        return $this->hasManyThrough(DispatcherInvoice::class, Order::class, 'customer_id', 'owner_id')
            ->where('owner_type', Order::class)
            ->where('customer_type', Customer::class);
    }

    public function servicesInvoices()
    {
        return $this->hasManyThrough(DispatcherInvoice::class, ServiceCenter::class, 'customer_id', 'owner_id')
            ->where('owner_type', ServiceCenter::class)
            ->where('customer_type', Customer::class);
    }

    function getBalanceInfoAttribute()
    {
        $invoicesQuery = DispatcherInvoice::query()
            ->where(function (Builder $q) {
                $q->whereHasMorph('owner', [Lead::class, Order::class], function (Builder $q) {
                    $q->whereHasMorph('customer', [Customer::class], function ($q) {
                        $q->where('dispatcher_customers.id', $this->id);
                    });
                });
            });
        $paidSum = $invoicesQuery->sum('paid_sum');
        $totalSum = Order::query()->forCustomer($this->id)->sum('amount');

        return [
            'paid_sum' => $paidSum,
            'total_sum' => $totalSum,
        ];
    }

    /**
     * Дубликат компании по ИНН
     * @return bool
     */
    function hasDuplicate()
    {
        if (!$this->hasRequisite() || $this->international_legal_requisites()->exists()) {
            return false;
        }
        $inn =
            $this->entity_requisites
                ? $this->entity_requisites->inn
                : $this->individual_requisites->inn;
        if (!$inn) {
            return false;
        }
        return self::query()
            ->forBranch($this->company_branch->id)
            ->where('id', '!=', $this->id)
            ->where('created_at', '<', $this->created_at)
            ->where(function (Builder $q) use (
                $inn
            ) {
                $q->whereHas('entity_requisites', function ($q) use (
                    $inn
                ) {
                    $q->where('inn', $inn);
                })->orWhereHas('individual_requisites', function ($q) use (
                    $inn
                ) {
                    $q->where('inn', $inn);
                });
            })
            ->orderBy('created_at')
            ->first();
    }

    function sendEmailNotification($queueInstance)
    {

        if ($this->email) {
            return Mail::to($this->email)->queue($queueInstance);
        }

        return false;
    }

    function removeDuplicate()
    {
        /** @var self $main */
        if ($main = $this->hasDuplicate()) {
            DB::beginTransaction();

            $main->remoteCompanyBranch()->syncWithoutDetaching($this->remoteCompanyBranch()->pluck('id'));

            $this->manager_notes()->update(['owner_id' => $main->id]);
            $this->leads()->update(['customer_id' => $main->id]);
            $this->orders()->update(['customer_id' => $main->id]);
            DB::table('employee_tasks_binds')->where('bind_type', self::class)
                ->where('bind_id', '=', $this->id)
                ->update([
                    'bind_id' => $main->id
                ]);
            DB::table('contacts')->where('owner_type', self::class)
                ->where('owner_id', '=', $this->id)
                ->update([
                    'owner_id' => $main->id
                ]);
            $this->delete();
            DB::commit();
        }
    }

    function remoteCompanyBranch()
    {
        return $this->belongsToMany(CompanyBranch::class, 'dispatcher_customers_company_branches');
    }

    function getCompanyNameAttribute($value)
    {
        return $value
            ?: trans('transbaza_register.account_type_comp')." #{$this->id}";
    }


    function scopeForDomain(
        $q,
        $domain_id = null
    ) {
        $domain_id =
            $domain_id
                ?: RequestHelper::requestDomain()->id;

        if (!$domain_id) {
            return $q;
        }
        return $q->where('dispatcher_customers.domain_id', $domain_id);
    }

    function scopeHasEmail(
        $query,
        $email
    ) {

        return $query->where(function ($query) use (
            $email
        ) {
            $query->whereHas('contacts', function ($q) use (
                $email
            ) {
                $q->whereHas('emails', function ($q) use (
                    $email
                ) {
                    $q->where('email', $email);
                });
            })->orWhere('email', $email);
        });

    }

    function addLegalRequisites($data)
    {
        if ($this->domain->alias === 'ru') {

            $requisite = $this->entity_requisites;
            if ($requisite) {
                $requisite->update($data);
            } else {
                $requisite = $this->entity_requisites()->save((new EntityRequisite($data)));
            }
            if (!empty($data['bank_requisites'])) {
                $requisite->addBankRequisites($data['bank_requisites']);
            }
        } else {
            if ($requisite = $this->international_legal_requisites) {
                $requisite->update($data);
            } else {
                $requisite = $this->international_legal_requisites()->save((new InternationalLegalDetails($data)));
            }
        }
        return $requisite;
    }

    function addIndividualRequisites($data)
    {
        if (empty($data['type'])) {
            $data['type'] = IndividualRequisite::TYPE_ENTREPRENEUR;
        }
        if ($data['type'] === IndividualRequisite::TYPE_PERSON) {
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
        } else {
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

        $driverLicence = $data['driver_licence'] ?? [];

        $instance =
            $this->individual_requisites
                ? $this->individual_requisites->update($data)
                : $this->individual_requisites()->save($requisite);

        if (!$this->individual_requisites) {
            $this->load('individual_requisites');
        }

        if ($driverLicence) {
            if ($this->individual_requisites->driverLicence->id) {
                $this->individual_requisites->driverLicence->update($driverLicence);
            } else {
                $this->individual_requisites->driverLicence()->save(new User\DriverLicence($driverLicence));
            }
        }
        return $this->individual_requisites->fresh();
    }

    function addToBlackList()
    {
        $this->update([
            'in_black_list' => true
        ]);
        return $this;
    }

    function removeFromBlackList()
    {
        $this->update([
            'in_black_list' => false
        ]);
        return $this;
    }

    function lastApplicationId(CustomerContract $contract)
    {
        return OrderComponent::query()->whereHas('order', function ($q) use ($contract) {
            $q->where('contract_id', $contract->id);
        })->max('application_id');

    }

    function getAndIncrementApplicationId(CustomerContract $contract)
    {
        $lock = Cache::lock("lock_application_{$this->id}", 10);

        if ($lock->get()) {

            $contract->increment('last_application_id');

            $lock->release();
        } else {
            $error = ValidationException::withMessages([
                'errors' => ['Генерация приложения недостпна в данный момент, попройте через несколько секунд.']
            ]);

            throw $error;
        }


        return $contract->last_application_id;
    }

    function contracts()
    {
        return $this->morphMany(CustomerContract::class, 'customer')
            ->where('type', 'rent')
            ->orderBy('id', 'desc');
    }

    function serviceContracts()
    {
        return $this->morphMany(CustomerContract::class, 'customer')->where('type', 'service')  ->orderBy('id', 'desc');
    }

    function services()
    {
     return $this->morphMany(ServiceCenter::class, 'customer');
    }

    function corpUsers()
    {
        return $this->belongsToMany(User::class, 'dispatcher_customers_users') ;
    }

    function generateContract($requisite, $data = [])
    {
        $branchSettings = $this->company_branch->getSettings();

        return
            $this->contracts->where('requisite_type', get_class($requisite))
                ->where('requisite_id', $requisite->id)
                ->first()
                ?: CustomerContract::generateContract($this, null, null, type: 'rent', requisite: $requisite);
    }

    function generateNewContract($requisite, $data = [], $type = 'rent')
    {
        return CustomerContract::generateContract($this, null, null, type: $type, requisite: $requisite,data: $data);
    }


    function generateServiceContract($requisite)
    {
        $branchSettings = $this->company_branch->getSettings();

        return
            $this->serviceContracts->where('requisite_type', get_class($requisite))
                ->where('requisite_id', $requisite->id)
                ->first()
                ?: CustomerContract::generateContract($this, null, null, null, 'service', $requisite);

    }

    function getCorpCabinetUrlAttribute()
    {
        /** @var Company $branch */
        $company = $this->company_branch->company;
        return $company->getUrl("customer/{$this->id}/proposals");
    }

    function getCompanyIdAttribute()
    {
        return $this->company_branch->company_id;
    }

    function generateCompanyLink(): string
    {
        /** @var CompanyBranch $branch */
        $branch = $this->company_branch;

        return $branch->getUrl("customers/{$this->id}/info");
    }

    function machinerySales()
    {
        return $this->morphMany(MachinerySale::class, 'customer');
    }

    function partsSales()
    {
        return $this->hasMany(PartsSale::class);
    }

}
