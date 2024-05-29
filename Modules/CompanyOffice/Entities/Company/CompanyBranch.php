<?php

namespace Modules\CompanyOffice\Entities\Company;

use App\City;
use App\Http\Controllers\Avito\Models\AvitoOrder;
use App\Http\Controllers\Avito\Models\AvitoOrderHistory;
use App\Http\Controllers\Avito\Models\AvitoStat;
use App\Machinery;
use App\Support\Region;
use App\User;
use App\User\EntityRequisite;
use App\User\IndividualRequisite;
use Google\Service\Docs\InsertTableColumnRequest;
use Illuminate\Database\Eloquent\Builder;
use App\Overrides\Model;
use Illuminate\Support\Facades\Auth;
use Modules\CompanyOffice\Entities\Company;
use Modules\CompanyOffice\Entities\CompanyTag;
use Modules\CompanyOffice\Entities\Employees\EmployeeTask;
use Modules\CompanyOffice\Entities\Expenditure;
use Modules\CompanyOffice\Services\CompanyRoles;
use Modules\CompanyOffice\Services\HasContacts;
use Modules\ContractorOffice\Entities\CompanyWorker;
use Modules\ContractorOffice\Entities\System\TariffUnitCompare;
use Modules\ContractorOffice\Entities\Vehicle\MachineryBase;
use Modules\ContractorOffice\Entities\Vehicle\Shop\MachinerySale;
use Modules\CorpCustomer\Entities\InternationalLegalDetails;
use Modules\Dispatcher\Entities\Customer;
use Modules\Dispatcher\Entities\DispatcherInvoice;
use Modules\Dispatcher\Entities\Lead;
use Modules\Dispatcher\Entities\PreLead;
use Modules\Integrations\Entities\Amo\AmoAuthToken;
use Modules\Integrations\Entities\Integration;
use Modules\Integrations\Entities\Mails\MailConnector;
use Modules\Integrations\Entities\MangoTelephony;
use Modules\Integrations\Entities\OneC\Connector;
use Modules\Integrations\Entities\SipuniTelephonyAccount;
use Modules\Integrations\Entities\Telpehony\TelephonyCallHistory;
use Modules\Integrations\Entities\Wialon;
use Modules\Integrations\Services\Amo\AmoUserService;
use Modules\Orders\Entities\Order;
use Modules\Orders\Entities\OrderDocument;
use Modules\Orders\Services\OrderTrait;
use Modules\PartsWarehouse\Entities\Warehouse\CompanyBranchWarehousePart;
use Modules\PartsWarehouse\Entities\Warehouse\Part;
use Modules\RestApi\Entities\Currency;
use Modules\RestApi\Transformers\VehicleSearch;
use Spatie\Permission\Models\Permission;

class CompanyBranch extends Model
{

    use HasContacts, OrderTrait;

    protected $fillable = [
        'name',
        'alias',
        'region_id',
        'city_id',
        'auto_prolongation',
        'avito_partner',
        'commission',
        'support_link',
        'price_without_vat',
        'company_id',
        'is_not_rf',
        'currency_code',
        'invoice_pay_days_count',
        'creator_id'
    ];

    protected $casts = [
        'auto_prolongation' => 'boolean',
        'avito_partner' => 'boolean',
        'price_without_vat' => 'boolean',
        'is_not_rf' => 'boolean',
    ];

    // protected $with = ['contacts', 'settings'];

    // protected $appends = ['hasRequisite'];

    public function ins_tariff_settings()
    {
        return $this->hasMany(InsTariffSetting::class);
    }

    public function ins_setting()
    {
        return $this->hasOne(InsSetting::class);
    }

    public function avito_stat()
    {
        return $this->hasOne(AvitoStat::class);
    }

    public function avito_orders()
    {
        return $this->hasMany(AvitoOrder::class);
    }

    public function avito_histories()
    {
        return $this->hasMany(AvitoOrderHistory::class);
    }

    public function client_bank_settings()
    {
        return $this->hasMany(ClientBankSetting::class);
    }

    public function google_api_settings()
    {
        return $this->hasOne(GoogleApiSetting::class);
    }

    public function ins_certificates()
    {
        return $this->hasMany(InsCertificate::class);
    }

    public function invoices()
    {
        return $this->hasMany(DispatcherInvoice::class);
    }


    protected static function boot()
    {
        parent::boot();

        self::created(function (self $model) {

            $model->createDefaultUnitCompares();
            $model->createDefaultSchedule();
        });

        self::deleted(function (self $model) {

            Permission::query()->where('name', 'like', "{$model->id}_branch%")->delete();

            $model->entity_requisites()->delete();
            $model->individual_requisites()->delete();

            if ($model->amoCrmAuth) {
                $service = new AmoUserService($model);
                try {

                    $service->removeIntegration();

                } catch (\Exception $exception) {
                    logger($exception->getMessage().' '.$exception->getTraceAsString());

                }

            }
        });
    }

    function wialonAccount()
    {
        return $this->hasOne(Wialon::class);
    }

    function documentsPack()
    {
        return $this->hasMany(DocumentsPack::class);
    }

    function documents()
    {
        return $this->morphMany(OrderDocument::class, 'order');
    }

    function getAllPhoneContactsAttribute()
    {

        $phones = ContactPhone::query()
            ->whereHas('contact', function (Builder $q) {
                $q->where(function (Builder $q) {
                    $q->whereHasMorph('owner', [CompanyWorker::class])
                        ->orWhereHasMorph('owner', [CompanyBranch::class]);
                })->forBranch($this->id);
            })
            ->pluck('phone')->toArray();

        $phones += $this->employees()->pluck('phone')->toArray();
        return $phones;
    }

    function tags()
    {
        return $this->hasMany(CompanyTag::class);
    }

    function user()
    {
        return $this->belongsTo(User::class, 'creator_id');
    }

    function schedule()
    {
        return $this->hasMany(CompanySchedule::class);
    }

    function daysOff()
    {
        return $this->hasMany(CompanyDayOff::class);
    }


    function region()
    {
        return $this->belongsTo(Region::class);
    }

    function getDomainAttribute()
    {
        return $this->company->domain;
    }

    function mailConnector()
    {
        return $this->morphOne(MailConnector::class, 'owner');
    }

    function commercialOffers()
    {
        return $this->hasMany(Company\Documents\CommercialOffer::class);
    }

    function city()
    {
        return $this->belongsTo(City::class);
    }

    function company()
    {
        return $this->belongsTo(Company::class);
    }

    function OneCConnection()
    {
        return $this->hasOne(Connector::class)->where('enable', true);
    }

    function settings()
    {
        return $this->hasOne(CompanyBranchSettings::class);
    }

    /**
     * Компания является заказчиком у другой компании через связь с "инфо" заказчиками
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    function remoteCustomer()
    {
        return $this->belongsToMany(Customer::class, 'dispatcher_customers_company_branches');
    }

    function createDefaultSchedule()
    {

        for ($i = 0; $i < 7; $i++) {

            /** @var CompanySchedule $schedule */
            $schedule = $this->schedule()->save(new CompanySchedule([
                'day_of_week' => $i,
            ]));

            $schedule->workHours()->save(new CompanyWorkHours([
                'time_from' => '08:00',
                'time_to' => '18:00',
            ]));
        };
    }

    function getAndIncrementLastMachinerySaleId()
    {
        $last = $this->settings->last_machinery_sale_id;
        $this->settings->increment('last_machinery_sale_id');
        return $last;
    }

    /**
     * @return mixed
     */
    function integration()
    {
        return $this->integrations->first();
    }

    function amoCrmAuth()
    {
        return $this->hasOne(AmoAuthToken::class);
    }

    function customers()
    {
        return $this->hasMany(Customer::class);
    }


    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    function integrations()
    {
        return $this->belongsToMany(Integration::class, 'company_branches_integrations')->withPivot('native_id');
    }


    function scopeUserHasAccess($q, $user_id = null, $role = CompanyRoles::ROLE_ADMINISTRATOR)
    {
        $user_id = $user_id ?: Auth::id();

        return $q->whereHas('employees', function ($q) use ($user_id, $role) {

            return $role === '*'
                ? $q->where('users.id', $user_id)
                : $q->where('users_company_branches.role', $role)
                    ->where('users.id', $user_id);
        });
    }


    function scopeForDomain($q, $domain = null)
    {
        $domain = $domain ?: request()->header('domain');

        return $q->whereHas('company', function ($q) use ($domain) {
            $q->forDomain($domain);
        });
    }

    function employees()
    {
        return $this->belongsToMany(User::class, 'users_company_branches')->withPivot('role', 'machinery_base_id');
    }

    function machinery_bases()
    {
        return $this->hasMany(MachineryBase::class);
    }




    function getBlockName($block)
    {
        return "{$this->id}_{$block}";
    }

    function leads()
    {
        return $this->hasMany(Lead::class);
    }

    function preLeads()
    {
        return $this->hasMany(PreLead::class);
    }

    function orders()
    {
        return $this->hasMany(Order::class);
    }

    function machines()
    {
        return $this->hasMany(Machinery::class);
    }

    function entity_requisites()
    {
        return $this->morphMany(User\EntityRequisite::class, 'requisite');
    }

    function sipuniTelephony()
    {
        return $this->hasOne(SipuniTelephonyAccount::class);
    }

    function mangoTelephony()
    {
        return $this->hasOne(MangoTelephony::class);
    }

    function individual_requisites()
    {
        return $this->morphMany(User\IndividualRequisite::class, 'requisite');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\MorphMany
     */
    public function international_legal_requisites()
    {
        return $this->morphMany(InternationalLegalDetails::class, 'requisite');
    }

    function warehouse_parts()
    {
        return $this->belongsToMany(Part::class, 'company_branches_warehouse_parts')
            ->using(CompanyBranchWarehousePart::class)
            ->withPivot([
                'id',
                'min_order',
                'min_order_type',
                'change_hour',
                'currency',
                'tariff_type',
                'is_rented',
                'is_for_sale',
                'default_sale_cost',
                'default_sale_cost_cashless',
                'default_sale_cost_cashless_vat',
                'name',
                'vendor_code',
            ]);
    }

    function getHasRequisiteAttribute()
    {
        return $this->entity_requisites()->exists() || $this->individual_requisites()->exists() || $this->international_legal_requisites()->exists();
    }

    function getRequisiteAttribute()
    {
        if (!$this->has_requisite) {
            return null;
        }

        return $this->entity_requisites->isNotEmpty()
            ? $this->entity_requisites
            : ($this->individual_requisites->isNotEmpty()
                ? $this->individual_requisites
                : $this->international_legal_requisites);

    }

    function findRequisiteByType($id, $type)
    {
        $requisite = null;

        switch ($type) {
            case 'legal':

                $requisite = $this->company->domain->alias === 'ru'
                    ? $this->entity_requisites()->find($id)
                    : $this->international_legal_requisites()->find($id);

                break;
            case 'individual':
                $requisite = $this->individual_requisites()->find($id);
                break;
        }

        return $requisite;
    }

    function getUrl($path = '', $params = [])
    {
        $domain = $this->company->domain;


        $path = $path ? '/'.trim($path, '/') : '';
        $params_string = http_build_query($params);

        $path = "http://{$this->company->alias}.{$domain->pure_url}/branch/{$this->id}".$path;

        $path = $params_string ? "{$path}?{$params_string}" : $path;

        return $path;
    }

    function sendEmailNotification($queueInstance, $needConfirm = true, $roles = [])
    {
        $roles = $roles ?: [CompanyRoles::ROLE_ADMIN];

        foreach ($this->employees()->wherePivotIn('role', $roles)->get() as $user) {
            $user->sendEmailNotification($queueInstance, $needConfirm);
        }
        return $this;
    }

    function getSettings()
    {
        return $this->settings ?: $this->settings()->save(new CompanyBranchSettings());
    }

    function expenditures()
    {
        return $this->hasMany(Expenditure::class);
    }


    function addLegalRequisites($data)
    {
        $requisite = $this->company->domain->alias === 'ru'
            ? new EntityRequisite($data)
            : new InternationalLegalDetails($data);
        $requisite['company_branch_id'] = $this->id;
        $this->entity_requisites()->save($requisite);

        return $requisite;
    }

    function addIndividualRequisites($data)
    {

        $requisite = new IndividualRequisite($data);
        $requisite['company_branch_id'] = $this->id;
        $this->individual_requisites()->save($requisite);

        return $requisite;
    }

    function getDashboardInfo()
    {

        $emails = ContactEmail::query()
            ->whereHas('contact', function (Builder $q) {
                $q->whereHasMorph('owner', [CompanyWorker::class], function ($q) {
                    $q->forBranch();
                });
            })->pluck('email')->toArray();
        return [
            'communication_emails' => ($this->mailConnector ? $this->mailConnector->getMails([
                'exclude_emails' => implode(',', $emails),
                'getCount' => 1
            ]) ?: 0 : 0),
            'today_machinery_service' => Machinery::query()->forBranch()->whereHas('freeDays', function ($q) {
                $q->whereNotNull('technical_work_id')
                    ->forPeriod(now()->startOfDay(), now()->endOfDay());
            })->count(),
            'busy_machines' => EmployeeTask::query()->forBranch()
                ->with('vehicles', 'employee')
                ->forPeriod(now()->startOfDay(), now()->endOfDay())
                ->whereHas('vehicles')->get(),
            'tasks' => EmployeeTask::query()
                ->where('employee_id', Auth::id())
                ->whereIn('status', [
                    EmployeeTask::STATUS_NEW,
                    EmployeeTask::STATUS_IN_PROGRESS
                ])->where('date_from', '<=', now()->endOfDay())->count(),
            'communication_calls' => TelephonyCallHistory::query()
                ->unprocessed()
                ->incoming()
                ->whereNotIn('phone', $this->all_phone_contacts)
                ->forCompany()->count(),
            'tomorrow_leads' => Lead::query()->forBranch($this->id)->startTomorrow()->count(),
            'today_orders' => Order::query()->forBranch($this->id)
                ->where(function ($q) {
                    $q->endToday()->where('status', Order::STATUS_ACCEPT);/*->orWhere(function (Builder $q) {
                        $q->where('status', Order::STATUS_ACCEPT)
                            ->earlierThanDate();
                    })*/;
                })->count(),
            'tomorrow_orders' => Order::query()->forBranch($this->id)
                ->where(function ($q) {
                    $q->endTomorrow(2)
                        ->where('status', Order::STATUS_ACCEPT)
                        /*->orWhere(function (Builder $q) {
                        $q->where('status', Order::STATUS_ACCEPT)
                            ->earlierThanDate();
                    })*/
                    ;
                })->count(),
        ];
    }


    function createDefaultUnitCompares()
    {
        TariffUnitCompare::query()->insert([
            [
                'name' => 'Тариф "Час"',
                'type' => 'hour',
                'amount' => 1,
                'company_branch_id' => $this->id,
            ],
            [
                'name' => 'Тариф "Смена"',
                'type' => 'shift',
                'amount' => 1,
                'company_branch_id' => $this->id,
            ],

        ]);
    }


    function generateSaleContract(MachinerySale $sale, $url, $title)
    {

        $lock = \Cache::lock("generate_sale_contract_{$sale->id}");

        try {
            if ($lock->get()) {

                $contract = new SaleContract([
                    'url' => $url,
                    'title' => $title,
                    'number' => $this->getAndIncrementLastMachinerySaleId(),
                    // $this->company_branch->getAndIncrementLastMachinerySaleId()
                    'prefix' => $this->settings->default_machinery_sale_contract_prefix,
                    'postfix' => $this->settings->default_machinery_sale_contract_postfix,
                    'created_at' => now(),
                    'company_branch_id' => $this->id
                ]);

                $contract->owner()->associate($sale);
                $contract->save();

                $lock->release();
            }
        } catch (\Exception $exception) {
            $lock->release();
            logger($exception->getMessage());
            throw new \InvalidArgumentException();
        }


        return $contract;

    }

    function getAllRequisites()
    {
        $requisites = [];
        $this->entity_requisites->each(function ($item) use (&$requisites) {
            $requisites[] = [
                'name' => $item->short_name,
                'vat_system' => $item->vat_system,
                'bank_requisites' => $item->bankRequisites,
                'value' => "legal_{$item->id}",
                'type' => "legal",
            ];
        });
        $this->international_legal_requisites->each(function ($item) use (&$requisites) {
            $requisites[] = [
                'name' => $item->account_name,
                'vat_system' => $item->vat_system,
                'value' => "legal_{$item->id}",
                'type' => "legal",
            ];
        });
        $this->individual_requisites->each(function ($item) use (&$requisites) {
            $requisites[] = [
                'name' => "{$item->surname} {$item->firstname} {$item->middlename}",
                'vat_system' => $item->vat_system,
                'value' => "individual_{$item->id}",
                'type' => $item->type,
            ];
        });
        return $requisites;
    }

    public function currency()
    {
        return $this->belongsTo(Currency::class, 'currency_code', 'code');
    }

}
