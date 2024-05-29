<?php

namespace Modules\Orders\Entities;

use App\City;
use App\Directories\TransactionType;
use App\Finance\TinkoffPayment;
use App\Http\Controllers\Avito\Models\AvitoOrder;
use App\Http\Controllers\Avito\Events\OrderChangedEvent;
use App\Jobs\AvitoNotificaion;
use App\Machinery;
use App\Machines\FreeDay;
use App\Machines\Type;
use App\Notifications\FireProposal;
use App\Service\EventNotifications;
use App\Service\RedirectLink;
use App\Service\RequestBranch;
use App\Support\Region;
use App\SystemCashHistory;
use App\User;
use App\User\BalanceHistory;
use App\User\Fine;
use Carbon\Carbon;
use EloquentFilter\Filterable;
use Illuminate\Database\Eloquent\Builder;
use App\Overrides\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Storage;
use Modules\CompanyOffice\Entities\Company\CompanyBranch;
use Modules\CompanyOffice\Entities\Company\DocumentsPack;
use Modules\CompanyOffice\Entities\Company\GoogleEvent;
use Modules\CompanyOffice\Entities\Employees\EmployeeTask;
use Modules\CompanyOffice\Services\BelongsToCompanyBranch;
use Modules\CompanyOffice\Services\CompaniesService;
use Modules\CompanyOffice\Services\HasContacts;
use Modules\CompanyOffice\Services\HasManager;
use Modules\CompanyOffice\Services\InternalNumbering;
use Modules\ContractorOffice\Entities\Driver;
use Modules\ContractorOffice\Entities\Vehicle\MachineryBase;
use Modules\ContractorOffice\Entities\Vehicle\Price;
use Modules\ContractorOffice\Filters\OrderFilter;
use Modules\ContractorOffice\Services\Tariffs\TimeCalculation;
use Modules\Dispatcher\Entities\ContractorPay;
use Modules\Dispatcher\Entities\Customer;
use Modules\Dispatcher\Entities\Directories\Contractor;
use Modules\Dispatcher\Entities\DispatcherInvoice;
use Modules\Dispatcher\Entities\DispatcherInvoiceDepositTransfer;
use Modules\Dispatcher\Entities\InvoiceItem;
use Modules\Dispatcher\Entities\Lead;
use Modules\Dispatcher\Http\Controllers\InvoiceController;
use Modules\Integrations\Entities\Telpehony\TelephonyCallHistory;
use Modules\Orders\Entities\Payments\InvoicePay;
use Modules\Orders\Jobs\SendOrderNotifications;
use Modules\Orders\Repositories\OrderRepository;
use Modules\Orders\Services\AvitoPayService;
use Modules\Orders\Services\OrderDocumentService;
use Modules\Orders\Services\OrderTrait;
use Modules\Orders\Services\WithHaving;
use Modules\PartsWarehouse\Entities\Warehouse\WarehousePartSet;
use Modules\Profiles\Entities\UserNotification;
use Modules\RestApi\Entities\Domain;
use OwenIt\Auditing\Auditable;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;

/**
 * Class Order
 * @package Modules\Orders\Entities
 */
class Order extends Model implements \OwenIt\Auditing\Contracts\Auditable
{

    use SoftDeletes, Auditable, OrderTrait, BelongsToCompanyBranch, HasContacts, HasManager, InternalNumbering, WithHaving, Filterable;

    /**
     *
     */
    const STATUS_OPEN = 'open';
    /**
     *
     */
    const STATUS_CLOSE = 'close';
    /**
     *
     */
    const STATUS_ACCEPT = 'accept';
    /**
     *
     */
    const STATUS_DONE = 'done';
    /**
     *
     */
    const STATUS_BLOCKED = 'blocked';
    /**
     *
     */
    const STATUS_FIRE = 'fire';
    /**
     *
     */
    const STATUS_HOLD = 'hold';
    const STATUS_REJECT = 'reject';
    const STATUS_PREPARE = 'prepare';
    const STATUS_START = 'start';
    const STATUS_FINISH = 'finish';
    const STATUS_UPD = 'upd';
    const STATUS_AGREED = 'agreed';
    const STATUS_INVOICE = 'invoice';


    /**
     *
     */
    const PROP_STATUS_LNG = [
        self::STATUS_OPEN => 'открыта',
        self::STATUS_CLOSE => 'закрыта',
        self::STATUS_ACCEPT => 'в работе',
        self::STATUS_DONE => 'завершен',
        self::STATUS_BLOCKED => 'заблокирован',
        self::STATUS_FIRE => 'Горящая',
        self::STATUS_HOLD => 'Ожидает оплаты',
    ];

    /**
     * @var
     */
    private $winning_users;

    /**
     * @var array
     */
    protected $fillable = [
        'status',
        'amount',
        'region_id',
        'city_id',
        'address',
        'coordinates',
        'date_from',
        'source',
        'system_commission',
        'contact_person',
        'start_time',
        'name',
        'domain_id',
        'creator_id',
        'contractor_id',
        'documents_pack_id',
        'company_branch_id',
        'machinery_base_id',
        'customer_id',
        'user_id',
        'external_id',
        'return_parts',
        'bank_requisite_id',
        'comment',
        'principal_id',
        'tmp_status',
        'contract_number',
        'type',
        'channel',
        'contract_id',
        'work_type',
        'driver_id',
        'from'
    ];

    /**
     * @var array
     */
    protected $auditInclude = [
        'status',
        'amount',
        'region_id',
        'city_id',
        'address',
        'coordinates',
        'date_from',
        'system_commission',
        'contact_person',
        'comment',
        'work_type',
        'start_time'
    ];

    /**
     * @var array
     */
    protected $appends = ['sum_format', 'payment_expires', 'time_left', 'can_cancel', 'status_fake', 'status_date'];

    /**
     * @var array
     */
 //   protected $with = ['vehicles', 'vehicle_timestamps', 'payment', 'documents', 'creator'];

    protected $casts = ['return_parts' => 'boolean', 'date_from' => 'datetime', 'created_at' => 'datetime'];

    protected static function boot()
    {
        parent::boot();

        self::created(function (self $order) {

            $companyService = new CompaniesService($order->company_branch->company);

            $order->setInternalNumber();

          /// $companyService->addUsersNotification(
          ///     trans('user_notifications.order_created', ['id' => $order->internal_number]),
          ///     Auth::user()
          ///         ?: null,
          ///     UserNotification::TYPE_INFO,
          ///     $order->generateCompanyLink(),
          ///     $order->company_branch);

        });
        self::updating(function (self $order) {
            if ($order->comment != $order->getOriginal('comment')) {
                dispatch(new AvitoNotificaion($order, 'В сделке добавлен новый комментарий',sms_send: false))->delay(Carbon::now()->addSeconds(5));
            }
        });
        self::updated(function (self $order) {
            OrderRepository::makeAvitoInvoice($order);
        });
    }

    function getModelFilterClass()
    {
        return OrderFilter::class;
    }

    function isAvitoOrder(): bool
    {
        return $this->channel=== 'avito';
    }

    function avito_order(): HasOne
    {
        return $this->hasOne(AvitoOrder::class);
    }

    /**
     * @param  array  $data
     * @return array
     */
    function transformAudit(array $data): array
    {
        if (Arr::has($data, 'new_values.coordinates') || Arr::has($data, 'old_values.coordinates')) {

            $data['old_values']['coordinates'] = getDbCoordinates($this->getOriginal('coordinates'));
            $data['new_values']['coordinates'] = $this->coordinates;
        }

        return $data;
    }

    function scopeActive($q)
    {
        return $q->whereNotIn('status', [self::STATUS_CLOSE, self::STATUS_DONE, self::STATUS_REJECT]);
    }

    function scopeForCustomer(
        Builder $builder,
        $customerId
    ) {
        $builder->whereHasMorph('customer', [Customer::class], function ($q) use (
            $customerId
        ) {
            $q->where('dispatcher_customers.id', $customerId);
        });

        return $builder;
    }

    function getSourceAttribute($val)
    {
        return $this->lead
            ? $this->lead->source
            : $val;
    }

    function scopeEndToday(Builder $q)
    {
        $q->whereHas('components', function (Builder $q) {
            $q->havingRaw('MAX(`date_to`) between ? and ?', [now()->startOfDay(), now()->endOfDay()]);
        });
    }

    function scopeStartToday(Builder $q)
    {
        $q->whereHas('components', function (Builder $q) {
            $q->havingRaw('MIN(`date_from`) between ? and ?', [now()->startOfDay(), now()->endOfDay()]);
        });
    }

    function scopeStartTomorrow(Builder $q)
    {
        $q->whereHas('components', function (Builder $q) {
            $q->havingRaw('MIN(`date_from`) between ? and ?', [now()->addDay()->startOfDay(), now()->addDay()->endOfDay()]);
        });
    }

    public function contract()
    {
        return $this->belongsTo(Customer\CustomerContract::class, 'contract_id')->withTrashed();
    }

    function scopeEndOn(
        Builder $q,
        Carbon $dateTime
    ) {
        $q->whereHas('components', function (Builder $q) use (
            $dateTime
        ) {
            $q->havingRaw('MAX(`date_to`) <= ?', [$dateTime]);
        });
    }

    function scopeEndTomorrow(
        Builder $q,
        $count = 2
    ) {
        $q->whereHas('components', function (Builder $q) use (
            $count
        ) {
            $q->havingRaw('MAX(`date_to`) between ? and ?', [now()->startOfDay(), now()->addDays($count)->endOfDay()]);
        });
    }

    function scopeEarlierThanDate(
        Builder $q,
        Carbon $date = null
    ) {
        $date =
            $date
                ?: now()->endOfDay();
        $q->whereHas('components', function (Builder $q) use (
            $date
        ) {
            $q->havingRaw('MAX(`date_to`) <= ?', [$date]);
        });
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    function vehicle_timestamps()
    {
        return $this->hasMany(MachineryStamp::class)->where('machinery_type',Machinery::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    function warehouse_part_set_timestamps()
    {
        return $this->hasMany(MachineryStamp::class)->where('machinery_type',WarehousePartSet::class);
    }

    function contractor()
    {
        return $this->belongsTo(CompanyBranch::class, 'contractor_id');
    }

    function driver()
    {
        return $this->belongsTo(Driver::class);
    }

    function contractorRequisite()
    {
        return $this->morphTo('contractor_requisite');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function types()
    {
        return $this->belongsToMany(Type::class, 'orders_need_type')->withPivot('brand_id', 'comment', 'waypoints',
            'params');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function region()
    {
        return $this->belongsTo(Region::class);
    }

    /**
     * @return HasOne
     */
    function customer_feedback()
    {
        return $this->hasOne(CustomerFeedback::class);
    }

    function customer()
    {
        return $this->morphTo();
    }

    function scopeArchive($q)
    {
        return $q->whereIn('status', [self::STATUS_CLOSE, self::STATUS_DONE]);
    }

    function scopeForPeriod(
        Builder $q,
        Carbon $dateFrom,
        Carbon $dateTo
    ) {
        $dateFrom->startOfDay();
        $dateTo->endOfDay();
        $q->whereHas('components', function (Builder $q) use (
            $dateFrom,
            $dateTo
        ) {
            $q->selectRaw("(CASE 
                        WHEN order_workers.order_type = 'shift'
                        THEN DATE_ADD(order_workers.date_from, INTERVAL (order_workers.order_duration - 1) DAY)
                         WHEN order_workers.order_type ='hour'
                        THEN DATE_ADD(order_workers.date_from, INTERVAL order_workers.order_duration HOUR)
                        END) as date_end");
            $q->havingBetween('date_end', [$dateFrom, $dateTo])
                ->orHavingRaw('date_from BETWEEN ? AND ?', [$dateFrom, $dateTo])
                ->orHavingRaw('(date_from <= ? AND date_end >= ?)', [$dateFrom, $dateFrom])
                ->orHavingRaw('(date_from <= ? AND date_end >= ?)', [$dateTo, $dateTo]);

            //   $q->whereRaw();
        });
        // logger($q->toSql());
        return $q;

    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    function contractor_pays()
    {
        return $this->hasMany(ContractorPay::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\MorphMany
     */
    function invoices()
    {
        return $this->morphMany(DispatcherInvoice::class, 'owner');
    }

    /**
     * @return mixed
     */
    function getInvoicesPaidAttribute()
    {
        return $this->invoices->sum('sum');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    function contractor_feedback()
    {
        return $this->hasMany(ContractorFeedback::class);
    }

    function documents()
    {
        return $this->morphMany(OrderDocument::class, 'order')->ordered();
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

    function dispatcher_contractors()
    {
        return $this->morphedByMany(Contractor::class, 'worker', 'order_workers')->withPivot(
            'amount', 'category_id', 'date_from', 'date_to', 'delivery_cost', 'order_type', 'order_duration',
            'regional_representative_commission', 'regional_representative_id', 'waypoints', 'params');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    function components()
    {
        return $this->hasMany(OrderComponent::class);
    }


    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function city()
    {
        return $this->belongsTo(City::class);
    }

    /**
     * @return mixed
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'creator_id')->withTrashed();
    }


    /**
     * @return HasOne
     */
    function payment()
    {
        return $this->hasOne(Payment::class);
    }

    /**
     * @return mixed
     */
    function getCanCancelAttribute()
    {
      return  is_object($this->date_from) ? (clone $this->date_from)->subHours(10)->gt(now()) : $this->date_from;
    }

    /**
     *
     */
    function cancel()
    {

        $this->payment->cancel();
        $this->order_days->each(function ($day) {
            $day->delete();
        });

        /*  foreach ($this->leads as $lead) {

              if ($lead->can_cancel) {
                  $lead->cancel();
              }else {
                  $lead->refuse();
              }

          }*/
    }

    function getOrderCategories()
    {
      // $unique = collect();

      // $categories = $this->getDispatcherCategories();

      // if ($categories->isNotEmpty()) {
      //     $unique = $unique->merge($categories);
      // }
      // $categories = $categories->merge($this->categories);

      // if ($categories->isNotEmpty()) {
      //     $unique = $unique->merge($categories);
      // }

       $unique = $this->categories->unique('id');

        $unique =
            $unique->map(function ($item)  {
                $item->count = $this->categories->where('id', $item->id)->count();

                return $item;
            });

        return $unique;
    }

    function getDispatcherCategories()
    {
        return $this->categories->where('pivot.worker_type', Contractor::class);
    }

    function categories()
    {
        return $this->belongsToMany(Type::class, 'order_workers', 'order_id', 'category_id')->withPivot('worker_type');
    }


    /**
     * Звонки из телефонии привязанные через коммуникацию
     * @return \Illuminate\Database\Eloquent\Relations\MorphMany
     */
    function calls()
    {
        return $this->morphMany(TelephonyCallHistory::class, 'bind');
    }


    function workers()
    {
        return $this->hasMany(OrderComponent::class);
    }

    function vehicles()
    {
        return $this->morphedByMany(Machinery::class, 'worker', 'order_workers')->withPivot(
            'id', 'complete', 'application_id', 'comment', 'cost_per_unit', 'return_delivery',
            'amount', 'category_id', 'date_from', 'date_to', 'delivery_cost', 'order_type', 'order_duration', 'status',
            'regional_representative_commission', 'regional_representative_id', 'reject_type');
    }

    function warehouse_part_sets()
    {
        return $this->morphedByMany(WarehousePartSet::class, 'worker', 'order_workers')->withPivot(
            'id', 'complete', 'application_id', 'comment', 'cost_per_unit', 'return_delivery',
            'amount', 'category_id', 'date_from', 'date_to', 'delivery_cost', 'order_type', 'order_duration',
            'regional_representative_commission', 'regional_representative_id', 'reject_type');
    }

    function leads()
    {
        return $this->belongsToMany(Lead::class, 'dispatcher_leads_orders');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    function domain()
    {
        return $this->belongsTo(Domain::class);
    }

    /**
     * @return mixed
     */
    function getLeadAttribute()
    {
        return $this->leads->first();
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    function order_days()
    {
        return $this->hasMany(FreeDay::class)->where('free_days.type', '=', 'order');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    function holds()
    {
        return $this->hasMany(FreeDay::class)->where('free_days.type', '=', 'hold');
    }


    /**
     * @param $query
     * @return mixed
     */
    function scopeCurrentUser($query)
    {
        return $query->where('user_id', Auth::user()->id);
    }

    function scopeContractorOrDispatcher(
        $q,
        $branchId = null
    ) {
        $branchId =
            $branchId
                ?: app()->make(RequestBranch::class)->companyBranch->id;
        return $q->where(function ($q) use (
            $branchId
        ) {
            $q->where('contractor_id', $branchId)
                ->orWhere(function ($q) use (
                    $branchId
                ) {
                    $q->whereType('dispatcher')
                        ->where('company_branch_id', $branchId);
                });
        });

    }

    function scopeCustomerOrders(
        Builder $query,
        $branchId = null
    ) {
        $branchId =
            $branchId
                ?:app()->make(RequestBranch::class)->companyBranch->id;
        return $query->where('customer_id', $branchId)
            ->where('customer_type', CompanyBranch::class);
    }

    function scopeContractorOrCustomer(
        $q,
        $branch_id = null
    ) {

        $branch_id =
            $branch_id
                ?: app()->make(RequestBranch::class)->companyBranch->id;

        return $q->where(function ($q) use (
            $branch_id
        ) {
            $q->where('company_branch_id', $branch_id)
                ->orWhere('contractor_id', $branch_id);
        });
    }

    /**
     * @param $q
     * @param  null  $domain_id
     * @return mixed
     */
    function scopeForDomain(
        $q,
        $domain_id = null
    ) {
        $domain_id =
            $domain_id
                ?: app(RequestBranch::class)->getDomain()->id;

        if (!$domain_id) {
            return $q;
        }
        return $q->whereDomainId($domain_id);

    }

    /**
     * @param $q
     * @param  null  $id
     */
    function scopeForRegionalRepresentative(
        $q,
        $id = null
    ) {
        $id =
            $id
                ?: Auth::id();

        $q->whereHas('user', function ($q) use (
            $id
        ) {
            $q->forRegionalRepresentative($id);
        })->orWhereHas('vehicles', function ($q) use (
            $id
        ) {
            $q->forRegionalRepresentative($id);
        });
    }

    /**
     * @param $q
     * @param  null  $branch_id
     * @return mixed
     */
    function scopeContractorOrders(
        $q,
        $branch_id = null
    ) {
        $branch_id =
            $branch_id
                ?: app()->make(RequestBranch::class)->companyBranch->id;

        return $q
            ->whereNotIn('status', [self::STATUS_OPEN, /*self::STATUS_HOLD*/])
            ->where('orders.contractor_id', $branch_id);

    }

    /**
     * @param $val
     */
    function setCoordinatesAttribute($val)
    {
        if ($val) {
            $coords = explode(',', str_replace('ST_', '', trim($val)));

            $query = "ST_GeomFromText('POINT($coords[0] $coords[1])')";
            $this->attributes['coordinates'] = \DB::raw($query);
        }
    }

    /**
     * Сумма для оплаты исполнителю трансбазы
     * @return mixed
     */
    function getContractorSum()
    {
        $sum = 0;

        $this->components->each(function ($component) use (
            &
            $sum
        ) {
            $sum += $component->total_sum;
            $sum += $component->services()->sum('price');
        });
        return $sum;
    }

    /**
     * Сумма для оплаты диспетчерскому подрядчику
     * @param $contractor_id
     * @return mixed
     */
    function getDispatcherContractorSum($contractor_id)
    {
        return $this->dispatcher_contractors->where('id', $contractor_id)->sum('pivot.amount');
    }

    /**
     * Сумма оплачных счетов диспетчерскому подрядчику
     * @return mixed
     */
    function getDispatcherContractorPaidSum($id)
    {

        return $this->contractor_pays->where('contractor_type', Contractor::class)->where('contractor_id',
            $id)->sum('sum');
    }

    /**
     * @param $value
     * @return string|null
     */
    function getCoordinatesAttribute($value)
    {
        return getDbCoordinates($value);
    }

    /**
     * @return string|null
     */
    function getPaymentExpiresAttribute()
    {
        return $this->status === self::STATUS_HOLD
            ? (string) clone ($this->created_at)->addMinutes(15)
            : null;
    }

    /**
     * @return int|null
     */
    function getTimeLeftAttribute()
    {
        return $this->status === self::STATUS_HOLD
            ? (integer) (clone ($this->created_at)->addMinutes(15))->diffInSeconds(now())
            : null;
    }

    /**
     * @return array
     */
    static function statuses()
    {
        return [
            self::STATUS_OPEN => trans('transbaza_statuses.proposal_open'),
            self::STATUS_CLOSE => trans('transbaza_statuses.proposal_close'),
            self::STATUS_ACCEPT => trans('transbaza_statuses.proposal_in_work'),
            self::STATUS_DONE => trans('transbaza_statuses.proposal_end'),
            self::STATUS_REJECT => trans('transbaza_statuses.proposal_cancel'),
            self::STATUS_BLOCKED => trans('transbaza_statuses.proposal_block'),
            self::STATUS_FIRE => trans('transbaza_statuses.proposal_fire'),
            self::STATUS_HOLD => trans('transbaza_order.pay_wait'),
            Lead::STATUS_CONTRACT => 'Договор',
            Lead::STATUS_INVOICE => 'Счет',
            self::STATUS_PREPARE => 'Подготовка к аренде',
            self::STATUS_START => 'Выдали/Начало работ',
            self::STATUS_FINISH => 'Вернули/Завершение работ',
            self::STATUS_UPD => 'УПД',
            self::STATUS_AGREED => 'Согласовано',

        ];
    }

    /**
     * @return mixed
     */
    function getStatusLangAttribute()
    {

        return self::statuses()[$this->status_fake];
    }

    /**
     * @return mixed
     */
    function getContractorSumAttribute()
    {

        return $this->components->sum(function ($component)  {
            return $component->total_sum + $component->services->sum(fn($service) =>  $service->price * $service->count);
        });
    }

    function getDispatcherContractors()
    {
        return $this->components->filter(function ($component) {
            return $component->worker->sub_owner_id;
        });
    }


    /**
     * Сумма оплаты подрядчику трансбазы
     * @return mixed
     */
    function getContractorPaidSum()
    {
        return $this->contractor_pays->where('contractor_type', User::class)->sum('sum');
    }


    /**
     * @return bool
     */
    function canAddContractorPay()
    {
        return $this->contractor_sum > $this->contractor_paid_sum;
    }

    function getHasInvoicesAttribute()
    {
        return $this->invoices()->exists();
    }

    function getNameAttribute($val)
    {
        if ($val) {
            return $val;
        }

        $name = trans('mails/mails_list.order_id', ['id' => $this->internal_number]);

        if ($this->lead) {
            $name .= " {$this->customer->company_name}";
        }

        return $name;
    }

    /**
     * @return string
     */
    function getTypesListTextAttribute()
    {
        if(\request()->has('no-type-list-text')){
            return '';
        }
        $list = clone $this->types;
        $list = $list->each(function ($v) {
            $count = $this->types->where('id', $v->id)->count();
            if ($count > 1 && !strpos($v->name, "({$count})")) {
                $v->name .= " ({$count})";
            }
        })->unique('id')->pluck('name');

        $string = implode(', ', $list->toArray());

        return trim($string, ',');
    }

    /**
     * @return string
     */
    function getSumFormatAttribute()
    {
        return humanSumFormat($this->amount);
    }


    /**
     * @return mixed
     */
    function getCurrencyAttribute()
    {
        return $this->company_branch->currency;
    }

    function depositTransfer()
    {
        return $this->morphedByMany(DispatcherInvoiceDepositTransfer::class,
            'owner',
            'dispatcher_invoices',
            'id', 'id', 'current_invoice_id');
    }

    /**
     * @param $vehicles
     */
    function attachVehicles($vehicles)
    {
        $attach = [];
        foreach ($vehicles as $vehicle) {
            $attach[$vehicle->id] = [
                'amount' => $vehicle->calculateCost($this->date_from, $this->date_to),
                'date_from' => $this->date_from,
                'date_to' => $this->date_to,
                'delivery_cost' => $vehicle->calculateDeliveryCost($this->coordinates)
                    ?: 0,
                'category_id' => $vehicle->category_id
            ];
            FreeDay::create([
                'startDate' => $this->date_from,
                'endDate' => $this->date_to,
                'type' => 'hold',
                'order_id' => $this->id,
                'machine_id' => $vehicle->id
            ]);
        }
        $this->vehicles()->attach($attach);

    }

    function attachDispatcherContractor(
        $contractor_id,
        $category_id,
        $cost_per_unit,
        Carbon $date_from,
        $order_type,
        $order_duration,
        $delivery_cost = 0
    ) {
        $date_to = getDateTo($date_from, $order_type, $order_duration);

        $this->dispatcher_contractors()->attach([
            $contractor_id => [
                'amount' => $cost_per_unit,
                'date_from' => $date_from,
                'date_to' => $date_to,
                'delivery_cost' => $delivery_cost,
                'order_type' => $order_type,
                'order_duration' => $order_duration,
                'category_id' => $category_id,
            ]
        ]);
    }

    /**
     * Добавление техники в заказ с указанными параметрами
     * @param $vehicle_id
     * @param $cost_per_unit
     * @param  Carbon  $date_from
     * @param $order_type
     * @param $order_duration
     * @param  int  $delivery_cost
     * @param  int  $return_delivery
     * @param  null  $waypoints
     * @param  null  $params
     * @param  null  $application_id
     * @param  null  $comment
     */
    function attachCustomVehicles(
        $vehicle_id,
        $cost_per_unit,
        Carbon $date_from,
        $order_type,
        $order_duration,
        $delivery_cost = 0,
        $return_delivery = 0,
        $waypoints = null,
        $params = null,
        $application_id = null,
        $comment = null,
        $type = 'machinery',
        $offset = null,
        $shift_duration = null
    ) {

        if ($type === 'warehouse_set') {

            $warehouseSet = WarehousePartSet::query()->findOrFail($vehicle_id);

            if ($warehouseSet->company_branch->getSettings()->price_without_vat && $this->contractorRequisite && $this->contractorRequisite->vat_system === Price::TYPE_CASHLESS_VAT) {

                $cost_per_unit = Price::addVat($cost_per_unit, $warehouseSet->company_branch->domain->country->vat);
                $delivery_cost = Price::addVat($delivery_cost, $warehouseSet->company_branch->domain->country->vat);
                $return_delivery = Price::addVat($return_delivery, $warehouseSet->company_branch->domain->country->vat);

            }

            $component = new OrderComponent([
                'order_id' => $this->id,
                'cost_per_unit' => $cost_per_unit,
                'date_from' => (string) $date_from,
                'date_to' => Carbon::parse($date_from)->addDays($order_duration)->subMinute(),
                'delivery_cost' => $delivery_cost,
                'return_delivery' => $return_delivery,
                'machinery_base_id' => $warehouseSet->machinery_base_id,
                'order_type' => $order_type,
                'order_duration' => $order_duration,
                'category_id' => $warehouseSet->type_id,
                'waypoints' => $waypoints,
                'params' => $params,
                'application_id' => $application_id,
                'contractor_application_id' => $contractorLastApplication ?? 0,
                'comment' => $comment,
            ]);

            $component->worker()->associate($warehouseSet);

            $component->save();

            return $component;
        }
        /** @var Machinery $vehicle */
        $vehicle = Machinery::findOrFail($vehicle_id);

        $vehicle->documents->each(function (OrderDocument $document) {
            if(OrderDocument::query()->where('order_id', $this->id)
            ->where('order_type', Order::class)
                ->where('url', $document->url)->doesntExist()

            ){
                $newDoc = $document->replicate();
                $newDoc->order_id = $this->id;
                $newDoc->order_type = Order::class;
                $newDoc->save();
            }
        });
        $isDayRent = $vehicle->change_hour === 24;

        $dates = $vehicle->getDatesForOrder($date_from, $order_duration, $order_type, $offset, forceAddDay: true);

        if(!isset($dates[0])) {
            throw new UnprocessableEntityHttpException('Техника занята.');
        }
        $date_from = Carbon::parse($dates[0]);
        $date_to =
            $order_type === TimeCalculation::TIME_TYPE_HOUR
                ? getDateTo($date_from, $order_type, $order_duration)
                : $dates[count($dates) - 1];

        if ($order_type === TimeCalculation::TIME_TYPE_SHIFT) {
            if (is_string($date_to)) {
                $date_to = Carbon::parse($date_to);
            }

            if ($isDayRent) {
                $diffInMinutes = $date_from->copy()->startOfDay()->diffInMinutes($date_from);

                $date_to->startOfDay()->addMinutes($diffInMinutes)->subMinute();
            } else {
                $endDay = $vehicle->getScheduleForDay($date_to->format('D'));
                if ($shift_duration){
                    if($shift_duration == 23){
                        $date_to->endOfDay();
                    }else {
                        $date_to->addHours($shift_duration);
                    }
                }else {
                    $date_to->startOfDay()
                        ->addHours($endDay->time_to[0])
                        ->addMinutes($endDay->time_to[1]);
                }
            }
        }

        if ($vehicle->company_branch->getSettings()->price_without_vat && $this->contractorRequisite && $this->contractorRequisite->vat_system === Price::TYPE_CASHLESS_VAT) {

            $cost_per_unit = Price::addVat($cost_per_unit, $vehicle->company_branch->domain->country->vat);
            $delivery_cost = Price::addVat($delivery_cost, $vehicle->company_branch->domain->country->vat);
            $return_delivery = Price::addVat($return_delivery, $vehicle->company_branch->domain->country->vat);

        }
        if ($vehicle->subOwner) {
            $contractorLastApplication = $vehicle->subOwner->getAndIncrementApplicationId();
        }
        $component = new OrderComponent([
            'order_id' => $this->id,
            'cost_per_unit' => $cost_per_unit,
            'date_from' => (string) $date_from,
            'date_to' => $date_to,
            'delivery_cost' => $delivery_cost,
            'return_delivery' => $return_delivery,
            'machinery_base_id' => $vehicle->base_id,
            'shift_duration'=> $shift_duration,
            'order_type' => $order_type,
            'order_duration' => $order_duration,
            'category_id' => $vehicle->category_id,
            'waypoints' => $waypoints,
            'params' => $params,
            'application_id' => $application_id,
            'contractor_application_id' => $contractorLastApplication ?? 0,
            'comment' => $comment,
        ]);

        $component->worker()->associate($vehicle);

        $component->save();

        $vehicle->generateOrderCalendar($dates, $order_type, $order_duration, $component, $date_from, $date_to);

        return $component;
    }


    /**
     * @return $this
     */
    private function holdDateToOrder()
    {
        $this->holds->each(function ($hold) {
            $hold->update([
                'type' => 'order',
                'order_id' => $this->id,
            ]);
        });

        return $this;
    }


    function getDateToAttribute()
    {

        $positions = $this->components->map(function ($position) {

            $date_to = $position->date_to;

            $position->end_date = strtotime((string) $date_to);

            return $position;
        });


        return Carbon::createFromTimestamp($positions->max('end_date'));

    }

    function getDateFromAttribute($val)
    {
        $positions = $this->components->map(function ($position) {

            $position->start_date = strtotime((string) $position->date_from);

            return $position;
        });

        $min = $positions->min('start_date');
        return $min
            ? Carbon::createFromTimestamp($min)
            : $val;
    }

    function principal()
    {
        return $this->belongsTo(User\PrincipalDoc::class, 'principal_id');
    }

    function getVehiclesCount()
    {
        return $this->components->unique('worker_id')->count();
    }


    function scopeHasWorker(
        Builder $q,
        $workerId = null
    ) {
        if ($workerId) {
            $q->whereHas('workers', function ($q) use (
                $workerId
            ) {
                $q->where('worker_type', Machinery::class)
                    ->where('worker_id', $workerId);
            });
        }
    }

    function machinerySets()
    {
        return $this->hasMany(MachinerySetsOrder::class);
    }

    /**
     *
     */
    function accept()
    {
        $this->holdDateToOrder();

        $this->update(['status' => self::STATUS_ACCEPT]);

        foreach ($this->leads as $lead) {
            $lead->accept();
        }

        //dispatch(new SendOrderNotifications($this));
    }

    public function google_event()
    {
        return $this->morphOne(GoogleEvent::class, 'eventable');
    }

    function pays()
    {
        return $this->hasManyThrough(InvoicePay::class, DispatcherInvoice::class, 'owner_id', 'invoice_id');
    }

    /**
     * @return $this
     */
    function done()
    {
        if ($this->status !== self::STATUS_ACCEPT) {
            return $this;
        }

        if (Cache::lock("done_order_{$this->id}", 10)->get()) {

            $this->update([
                'status' => Order::STATUS_DONE,
                'tmp_status' => Order::STATUS_DONE,
            ]);

            if ($this->lead && !$this->lead->isArchived()) {
                $this->lead->done();
            }

            if($this->isAvitoOrder()) {
                if($this->components()->exists()) {
//                    $upd = $this->documents()->where('type', 'upd')->first();
//                    if(!$upd) {
//                        (new OrderDocumentService([]))->formSingleAct($this, 'default_upd_url', true);
//                    }

                }
                OrderChangedEvent::dispatch($this, AvitoOrder::STATUS_FINISHED);
            }
            $companyService = new CompaniesService($this->company_branch->company);

            $companyService->addUsersNotification(
                trans('user_notifications.order_done', ['id' => $this->internal_number]),
                Auth::user()
                    ?: null,
                UserNotification::TYPE_SUCCESS,
                $this->generateCompanyLink(),
                $this->company_branch);

            // $this->rewardContractors();


            Cache::lock('done_order')->release();

        }


    }

    function archive()
    {
        if ($this->status !== self::STATUS_ACCEPT) {
            return $this;
        }

        if (Cache::lock('archive_order', 10)->get()) {

            $this->update([
                'status' => Order::STATUS_CLOSE
            ]);

            if ($this->lead) {
                $this->lead->close();
            }

            $companyService = new CompaniesService($this->company_branch->company);

            $companyService->addUsersNotification(
                trans('user_notifications.order_done', ['id' => $this->internal_number]),
                Auth::user()
                    ?: null,
                UserNotification::TYPE_ERROR,
                $this->generateCompanyLink(),
                $this->company_branch);

            // $this->rewardContractors();


            Cache::lock('archive_order')->release();
        }


    }

    function documentsPack()
    {
        return $this->belongsTo(DocumentsPack::class);
    }

    function returnToWork()
    {
        /*   if (!in_array($this->status, [self::STATUS_DONE, self::STATUS_REJECT])) {
               return $this;
           }*/

        if (Cache::lock('done_order', 10)->get()) {

            $this->update([
                'status' => Order::STATUS_ACCEPT,
                'tmp_status' => Order::STATUS_ACCEPT,
            ]);

            if($this->isAvitoOrder()) {
                OrderChangedEvent::dispatch($this, AvitoOrder::STATUS_CREATED);
            }

            $companyService = new CompaniesService($this->company_branch->company);

            $companyService->addUsersNotification(
                trans('user_notifications.order_return', ['id' => $this->internal_number]),
                Auth::user()
                    ?: null,
                UserNotification::TYPE_INFO,
                $this->generateCompanyLink(),
                $this->company_branch);

            // $this->rewardContractors();


            Cache::lock('done_order')->release();
        }
    }

    /**
     * @param $machine_id
     * @param $type
     * @param  Carbon  $dateTime
     * @param  null  $coordinates
     * @return $this|bool
     */
    function addMachineryCoordinates(
        $machine_id,
        $type,
        Carbon $dateTime,
        $coordinates = null
    ) {

        $count = $this->vehicle_timestamps->where('machinery_id', $machine_id)->count();

        switch ($count) {
            case 0:
                if ($type === 'on_the_way') {
                    MachineryStamp::createTimestamp($machine_id, $this->id, $type, $dateTime, $coordinates);
                } else {
                    return false;
                }
                break;
            case 1:
                if ($type === 'arrival') {
                    MachineryStamp::createTimestamp($machine_id, $this->id, $type, $dateTime, $coordinates);
                } else {
                    return false;
                }
                break;
            case 2:
                if ($type === 'done') {
                    MachineryStamp::createTimestamp($machine_id, $this->id, $type, $dateTime, $coordinates);
                } else {
                    return false;
                }
                break;
            default:
                return false;
        }

        return $this;
    }

    /**
     * @param $amount
     * @return float|int
     */
    function calculateVehicleCommission($amount)
    {
        return $amount * (1 - $this->system_commission / 100 / 100);
    }

    /**
     * @return float|int
     */
    function calculateSystemCommission()
    {
        return $this->amount * $this->system_commission / 100 / 100;
    }


    /**
     *
     */
    function rewardContractors()
    {
        foreach ($this->vehicles as $vehicle) {

            $reward =
                $this->leads()->exists()
                    ? 0
                    : $this->calculateVehicleCommission($vehicle->pivot->amount);

            BalanceHistory::create([
                'user_id' => $vehicle->user->id,
                'admin_id' => 0,
                'old_sum' => $vehicle->user->getBalance('contractor'),
                'new_sum' => $vehicle->user->getBalance('contractor') + $reward,
                'type' => BalanceHistory::getTypeKey('reward'),
                'billing_type' => 'contractor',
                'sum' => $reward,
                'reason' => TransactionType::getTypeLng('reward').' #'.$this->id,
            ]);

            $vehicle->user->incrementContractorBalance($reward);
        }

        $current_system_cash = SystemCashHistory::getCurrentCash();

        $commission =
            $this->leads()->exists()
                ? 0
                : $this->calculateSystemCommission();

        SystemCashHistory::create([
            'old_sum' => $current_system_cash,
            'new_sum' => $current_system_cash + $commission,
            'sum' => $commission,
            'type' => 0,
            'reason' => 'Выполненый заказ'.' #'.$this->id,
        ]);
        SystemCashHistory::incrementCash($this->calculateSystemCommission());
    }
    function getAmountAttribute($val)
    {
//        if(\request()->has('no-recalc')){
//            return DB::table('orders')->select('amount')->where('id',$this->id)->first()->amount;
//        }

        $sum = 0;
        if ($this->machinerySets->count()) {
            foreach ($this->machinerySets as $set) {
                $sum += ($set->prices->sum ?? 0);
            }
        } else {
            $sum = $this->components->filter(function ($component) {
                return $component->status !== self::STATUS_REJECT;
            })->sum('total_sum_with_services');
        }

        if ($val !== $sum) {
            $this->update(['amount' => $sum]);
        }
        return $sum;
    }

    function scopeRejected(Builder $q)
    {
        return $q->whereHas('components', function (Builder $q) {
            $q->where('status', '=', self::STATUS_REJECT);
        });
    }

    function isVatSystem()
    {
        return $this->company_branch->getSettings()->price_without_vat && $this->contractorRequisite && $this->contractorRequisite->vat_system === Price::TYPE_CASHLESS_VAT;
    }

    /**
     * @param $user_type
     * @return $this
     */
    function refuse($user_type)
    {

        switch ($user_type) {
            case 'contractor':
                $user = $this->winner_offer->user;
                switch ($this->contractor_timestamps->winner_steps) {
                    case 0:
                        //  $fine_percent = 10;
                        $fine_percent =
                            (Carbon::now()->diffInHours($this->date) > 12)
                                ? 0
                                : 10;
                        break;
                    case 1:
                    case 2:
                    case 3:
                        $fine_percent = 15;
                        break;
                    default:
                        $fine_percent = 10;
                        break;

                }

                $offer = $this->winner_offer;

                if ($regional = $offer->user->regional_representative) {
                    $regional->notify(new FireProposal($this, $user));
                }
                $fine = $this->amount * $fine_percent / 100;
                $user->decrementContractorBalance($fine);

                $balance_type = 'contractor';
                break;
            case 'customer':
                $balance_type = 'customer';
                switch ($this->contractor_timestamps->winner_steps) {
                    case 0:
                        $fine_percent =
                            (Carbon::now()->diffInHours($this->date) > 12)
                                ? 0
                                : 10;
                        break;
                    case 1:
                        $fine_percent = 20;
                        break;
                    case 2:
                        $fine_percent = 20;
                        break;
                    case 3:
                        $fine_percent = 30;
                        break;
                }
                $user = $this->user;


                $fine = $this->amount * $fine_percent / 100;
                $user->decrementCustomerBalance($fine);

                break;
        }
        $this->orderDays->each(function ($day) {
            $day->delete();
        });
        $this->contractor_timestamps->update([
            'winner_steps' => 0,
            'machinery_ready' => null,
            'machinery_on_site' => null,
            'end_of_work' => null,
        ]);
        if ($fine !== 0) {
            BalanceHistory::create([
                'user_id' => $user->id,
                'admin_id' => 0,
                'old_sum' => $user->getBalance($balance_type) + $fine,
                'new_sum' => $user->getBalance($balance_type),
                'type' => BalanceHistory::getTypeKey('fine'),
                'billing_type' => $balance_type,
                'sum' => $fine,
                'reason' => TransactionType::getTypeLng('fine').' Заказ #'.$this->id,
            ]);

            Fine::create([
                'sum' => $fine,
                'user_id' => $user->id,
                'proposal_id' => $this->id,
                'order_step' => 0,
            ]);

            $current_system_cash = SystemCashHistory::getCurrentCash();
            SystemCashHistory::create([
                'old_sum' => $current_system_cash,
                'new_sum' => $current_system_cash + $fine,
                'sum' => $fine,
                'type' => 0,
                'reason' => 'Штраф за заказ'.' #'.$this->id,
            ]);
            SystemCashHistory::incrementCash($fine);
        }

        if ($balance_type === 'customer') {
            return $this->deleteByCustomer();
        }

        $this->status = self::STATUS_FIRE;


        $this->save();


        return $this;
    }

    function isClosed()
    {
        return in_array($this->status, [
            self::STATUS_CLOSE,
            self::STATUS_DONE
        ]);
    }


    function generateCompanyLink(): string
    {
        /** @var CompanyBranch $branch */
        $branch = $this->company_branch;

        return $branch->getUrl("orders/{$this->id}");
    }

    /**
     * @return string
     */
    function generateContractorLink()
    {
        return origin('/contractor-profile/orders/'.$this->id, [], $this->domain);

    }

    /**
     * @return string
     */
    function generateCustomerLink()
    {
        return origin('/order/'.$this->id, [], $this->domain);

    }


    function getContractUrl($subContractorId = null)
    {
        $lead = $this->lead;

        return ($lead && $lead->contract)
            ? $lead->contract->getOrderContractUrl($this->id, $subContractorId)
            : false;
    }

    function creator()
    {
        return $this->belongsTo(User::class, 'user_id')->setEagerLoads([]);
    }

    function getMachineryBaseAttribute()
    {
        if ($this->base) {
            return $this->base;
        }
        $position = $this->components->filter(function ($item) {

            return (bool) $item->machinery_base_id;
        })->first();

        return $position
            ? $position->machineryBase
            : null;
    }

    function base()
    {
        return $this->belongsTo(MachineryBase::class, 'machinery_base_id');
    }

    function media()
    {
        return $this->morphMany(OrderMedia::class, 'owner');
    }

    function bankRequisite()
    {
        return $this->belongsTo(User\BankRequisite::class, 'bank_requisite_id');
    }

    function upd()
    {
        return $this->morphOne(UdpRegistry::class, 'parent');
    }

    function getStatusFakeAttribute() {
        $val = $this->status;

//        if($this->vehicle_timestamps->filter(fn($stamp) => $stamp->type === 'done')->count() === $this->vehicles->unique('id')->count() && $this->vehicles->unique('id')->count() > 0) {
//            $val = self::STATUS_FINISH;
//        }
//
//        if($this->status === self::STATUS_DONE) {
//            $val = self::STATUS_DONE;
//        }
//
//        if($this->status === self::STATUS_CLOSE || $this->status === self::STATUS_REJECT) {
//            $val = self::STATUS_REJECT;
//        }


        if(!$this->isAvitoOrder() && !$this->tmp_status) {
            $this->update([
                'tmp_status' => $val
            ]);
        }

        return $this->tmp_status;
    }

    function getStatusDateAttribute()
    {
        if( $this->status === Lead::STATUS_CONTRACT && $this->contract_sent ) {
            $status =  $this->contract_sent;
        }
        if($this->status === Lead::STATUS_INVOICE) {
            $status =  $this->invoices->last()?->updated_at;
        }

        if($stamp = $this->vehicle_timestamps->first(fn($stamp) => $stamp->type === 'on_the_way')) {
            $status =  $stamp->created_at;
        }

        if($stamp =$this->vehicle_timestamps->first(fn($stamp) => $stamp->type === 'arrival')) {
            $status =  $stamp->created_at;
        }

        if($this->vehicle_timestamps->filter(fn($stamp) => $stamp->type === 'done')->count() === $this->vehicles->unique('id')->count()) {
            $stamp = $this->vehicle_timestamps->first(fn($stamp) => $stamp->type === 'done');
            if($stamp) {
                $status =  $stamp->created_at;
            }
        }

        if($stamp =$this->warehouse_part_set_timestamps->first(fn($stamp) => in_array($stamp->type, ['on_the_way', 'arrival', 'done']))) {
             $status =  $stamp->created_at;
        }

        if($this->upd) {
            $status =  $this->upd->updated_at;
        }

        return $status ?? null;
    }


}
