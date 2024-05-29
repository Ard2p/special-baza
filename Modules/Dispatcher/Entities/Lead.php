<?php

namespace Modules\Dispatcher\Entities;

use App\City;
use App\Directories\LeadRejectReason;
use App\Helpers\RequestHelper;
use App\Machinery;
use App\Machines\Type;
use App\Overrides\Model;
use App\Service\Insurance\InsuranceService;
use App\Service\RequestBranch;
use App\Support\Region;
use App\User;
use Carbon\Carbon;
use Exception;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Modules\CompanyOffice\Entities\Company\CompanyBranch;
use Modules\CompanyOffice\Entities\Company\DocumentsPack;
use Modules\CompanyOffice\Entities\Company\GoogleEvent;
use Modules\CompanyOffice\Services\BelongsToCompanyBranch;
use Modules\CompanyOffice\Services\CompaniesService;
use Modules\CompanyOffice\Services\HasContacts;
use Modules\CompanyOffice\Services\HasManager;
use Modules\CompanyOffice\Services\InternalNumbering;
use Modules\ContractorOffice\Entities\CompanyWorker;
use Modules\ContractorOffice\Entities\System\Tariff;
use Modules\ContractorOffice\Entities\Vehicle\Price;
use Modules\ContractorOffice\Services\Tariffs\TimeCalculation;
use Modules\Dispatcher\Entities\Customer\CustomerContract;
use Modules\Dispatcher\Entities\Directories\Contractor;
use Modules\Dispatcher\Entities\Documents\Contract;
use Modules\Dispatcher\Services\ContractTrait;
use Modules\Dispatcher\Transformers\TbContractor;
use Modules\Integrations\Entities\Amo\AmoLead;
use Modules\Integrations\Entities\Telpehony\TelephonyCallHistory;
use Modules\Orders\Entities\MachineryStamp;
use Modules\Orders\Entities\Order;
use Modules\Orders\Entities\OrderComponentService;
use Modules\Orders\Entities\OrderDocument;
use Modules\Orders\Entities\OrderManagement;
use Modules\Orders\Entities\Payment;
use Modules\Orders\Services\OrderTrait;
use Modules\PartsWarehouse\Entities\Stock\Item;
use Modules\PartsWarehouse\Entities\Stock\ItemSerial;
use Modules\PartsWarehouse\Entities\Stock\Stock;
use Modules\PartsWarehouse\Entities\Warehouse\CompanyBranchWarehousePart;
use Modules\PartsWarehouse\Entities\Warehouse\WarehousePartSet;
use Modules\PartsWarehouse\Entities\Warehouse\WarehousePartsOperation;
use Modules\Profiles\Entities\UserNotification;
use Modules\RestApi\Entities\Domain;
use Modules\RestApi\Transformers\VehicleSearch;
use OwenIt\Auditing\Auditable;
use TypeError;

class Lead extends Model implements \OwenIt\Auditing\Contracts\Auditable
{

    use SoftDeletes, Auditable, ContractTrait, BelongsToCompanyBranch, HasContacts, HasManager, InternalNumbering, OrderTrait;

    protected $table = 'dispatcher_leads';

    protected $fillable = [
        'title',
        'customer_name',
        'phone',
        'email',
        'publish_type',
        'is_fast_order',
        'city_id',
        'address',
        'comment',
        'start_date',
        'status',
        'coordinates',
        'region_id',
        'domain_id',
        'customer_id',
        'pay_type',
        'reject_type',
        'source',
        'rejected',
        'integration_unique',
        'creator_id',
        'object_name',
        'documents_pack_id',
        'contractor_requisite_type',
        'contract_sent',
        'tmp_status',
        'contractor_requisite_id',
        'company_branch_id',
        'tender',
        'kp_date',
        'accepted',
        'first_date_rent',
        'customer_contract_id',
    ];

    protected $auditInclude = [
        'customer_name',
        'phone',
        'city_id',
        'address',
        'comment',
        'start_date',
        'status',
        'coordinates'
    ];

    protected $casts = [
        'tender' => 'boolean',
        'accepted' => 'boolean',
    ];

    protected $appends = ['date', 'time', 'can_edit', 'dispatcher_sum', 'type', 'status_lng', 'status_date'];

    // protected $with = ['categories', 'contract'];

    protected $hidden = ['tb_contractors'];


    //protected $dateFormat = 'Y-m-d H:i';

    const STATUS_OPEN = 'open';
    const STATUS_ACCEPT = 'accept';
    const STATUS_INVOICE = 'invoice';
    const STATUS_CANCEL = 'cancel';
    const STATUS_DONE = 'done';
    const STATUS_CLOSE = 'close';
    const STATUS_EXPIRED = 'expired';
    const STATUS_CONTRACT = 'contract';
    const STATUS_AGREED = 'agreed';
    const STATUS_KP = 'kp';

    const PUBLISH_MAIN = 'my_proposals';
    const PUBLISH_ALL_CONTRACTORS = 'all_contractors';
    const PUBLISH_FOR_COMPANIES = 'for_companies';

    const SOURCE_TB = 'transbaza';
    const SOURCE_MAIL = 'mail';
    const SOURCE_CALL = 'call';
    const SOURCE_WHATSAPP = 'whatsapp';

    protected $dates = ['start_date', 'first_date_rent', 'kp_date'];

    private $received_contractors = null;

    static function getStatuses()
    {
        return [
            [
                'name' => 'Новая',
                'value' => self::STATUS_OPEN,
            ],
            [
                'name' => trans('transbaza_statuses.proposal_in_work'),
                'value' => self::STATUS_ACCEPT,
            ],
            [
                'name' => trans('transbaza_statuses.proposal_cancel'),
                'value' => self::STATUS_CANCEL,
            ],
            [
                'name' => trans('transbaza_statuses.proposal_done'),
                'value' => self::STATUS_DONE,
            ],
            [
                'name' => trans('transbaza_statuses.proposal_close'),
                'value' => self::STATUS_CLOSE,
            ],
            [
                'name' => trans('transbaza_statuses.proposal_expired'),
                'value' => self::STATUS_EXPIRED,
            ],
            [
                'name' => 'Согласовано',
                'value' => self::STATUS_AGREED,
            ],
            [
                'name' => 'Договор',
                'value' => self::STATUS_CONTRACT,
            ],
            [
                'name' => 'КП',
                'value' => self::STATUS_KP,
            ],
            [
                'name' => 'Счет',
                'value' => self::STATUS_INVOICE,
            ],
            [
                'name' => 'Отказ',
                'value' => 'reject',
            ],
        ];
    }

    function rejectType()
    {
        return $this->hasOne(LeadRejectReason::class, 'key', 'reject_type');
    }

    function documents()
    {
        return $this->morphMany(OrderDocument::class, 'order');
    }

    function documentsPack()
    {
        return $this->belongsTo(DocumentsPack::class);
    }

    function contractorRequisite()
    {
        return $this->morphTo('contractor_requisite');
    }

    function transformAudit(array $data): array
    {
        if (Arr::has($data, 'new_values.coordinates') || Arr::has($data, 'old_values.coordinates')) {

            $data['old_values']['coordinates'] = getDbCoordinates($this->getOriginal('coordinates'));
            $data['new_values']['coordinates'] = $this->coordinates;
        }

        return $data;
    }

    function setCoordinatesAttribute($val)
    {
        if ($val) {
            $coords = explode(',', trim($val));
            $query = "ST_GeomFromText('POINT($coords[0] $coords[1])')";
            $this->attributes['coordinates'] = \DB::raw($query);
        }
    }

    function setPayTypeAttribute($val)
    {
        if (!in_array($val, Price::getTypes())) {
            $this->attributes['pay_type'] = Price::TYPE_CASHLESS_WITHOUT_VAT;
        }

        $this->attributes['pay_type'] = $val;
    }

    function getTypeAttribute()
    {
        return $this->customer instanceof User
            ? 'client'
            : 'dispatcher';
    }


    function getCoordinatesAttribute($value)
    {
        return getDbCoordinates($value);
    }

    function categories()
    {
        return $this->belongsToMany(Type::class, 'lead_positions')->withPivot([
            'order_type', 'order_duration', 'count', 'date_from', 'waypoints', 'params', 'machinery_model_id', 'id', 'optional_attributes'
        ]);
    }

    public function google_event()
    {
        return $this->morphOne(GoogleEvent::class, 'eventable');
    }

    function positions()
    {
        return $this->hasMany(LeadPosition::class);
    }

    function invoices()
    {
        return $this->morphMany(DispatcherInvoice::class, 'owner');
    }

    function calls()
    {
        return $this->morphMany(TelephonyCallHistory::class, 'bind');
    }

    function amoIntegration()
    {
        return $this->hasOne(AmoLead::class);
    }

    function getIntegrationAttribute()
    {
        return $this->amoIntegration;
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'creator_id')->withTrashed();
    }


    function domain()
    {
        return $this->belongsTo(Domain::class);
    }

    /**
     * Договор к заявке
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    function contract()
    {
        return $this->hasOne(Contract::class);
    }

    function customerContract()
    {
        return $this->belongsTo(CustomerContract::class);
    }

    function city()
    {
        return $this->belongsTo(City::class);
    }

    function region()
    {
        return $this->belongsTo(Region::class);
    }

    function offers()
    {
        return $this->hasMany(LeadOffer::class, 'lead_id');
    }

    function orders()
    {
        return $this->belongsToMany(Order::class, 'dispatcher_leads_orders');
    }


    function getContractorsAttribute()
    {
        return TbContractor::collection($this->orders->pluck('contractor'));
    }

    function getDispatcherContractorsAttribute()
    {
        return $this->orders->pluck('dispatcher_contractors');
    }

    function customer()
    {
        return $this->morphTo('customer');
    }

    function getStatusLngAttribute()
    {
        $array = self::getStatuses();

        $key = array_search($this->tmp_status, array_column($array, 'value'));

        return $array[$key]['name'] ?? '';
    }

    function getStatusAttribute($val) {
//        if($val === self::STATUS_DONE) {
//            if($this->tmp_status !== $val) {
//                $this->update([
//                    'tmp_status' => $val
//                ]);
//            }
//            return  $val;
//        }
//        if($val === self::STATUS_CLOSE) {
//            if($this->tmp_status !== $val) {
//                $this->update([
//                    'tmp_status' => $val
//                ]);
//            }
//            return  $val;
//        }
//        if (!$this->comment && $val === self::STATUS_ACCEPT) {
//            $val = self::STATUS_OPEN;
//        }
//        if ($this->comment && $val === self::STATUS_OPEN) {
//            $val = self::STATUS_ACCEPT;
//            $this->update([
//                'tmp_status' => $val
//            ]);
//
//        }

        if(!$this->tmp_status) {
            $this->update([
                'tmp_status' => $val
            ]);
        }

        return $val;
    }

    function getStatusDateAttribute()
    {
        if ($this->status === self::STATUS_CONTRACT && $this->contract_sent) {
            return $this->contract_sent;
        }
        if ($this->status === self::STATUS_INVOICE) {
            return $this->invoices->last()?->updated_at;
        }
        return $this->updated_at;
    }

    function getInWorkCategories()
    {
        $categories = collect();

        foreach ($this->orders as $order) {
            $categories = $categories->merge(
                $order->types
            );
        }

        return $categories->groupBy('id')->map(function ($categories) {
            return $categories->count();
        });
    }

    function getCanEditAttribute()
    {
        return in_array($this->status, [
                self::STATUS_OPEN,
                self::STATUS_KP
            ]);
    }


    function accept()
    {
        $this->update(['status' => self::STATUS_ACCEPT]);

        return $this;
    }

    function refuse()
    {
        $this->update(['status' => self::STATUS_OPEN]);

        return $this;
    }

    function cancel()
    {
        $this->update(['status' => self::STATUS_CANCEL]);

        return $this;
    }

    function done()
    {
        $this->update(['status' => self::STATUS_DONE]);

        $companyService = new CompaniesService($this->company_branch->company);
        $companyService->addUsersNotification(
            trans('user_notifications.proposal_done', ['id' => $this->internal_number]),
            Auth::user()
                ?: null,
            UserNotification::TYPE_SUCCESS,
            $this->generateCompanyLink(),
            $this->company_branch);

        return $this;
    }

    function close()
    {
        $this->update(['status' => self::STATUS_CLOSE]);

        return $this;
    }

    function reject(
        $type,
        $reason = ''
    ) {
        $this->update([
            'status' => self::STATUS_CLOSE,
            'rejected' => $reason,
            'reject_type' => $type,
        ]);

        return $this;
    }

    function pullFromArchive()
    {

        $this->update([
            'status' => ($this->orders()->exists()
                ? self::STATUS_ACCEPT
                : self::STATUS_OPEN)
        ]);

        return $this;
    }

    function userAvailableQuery(Builder $branches)
    {
        $branches->where(function ($branches) {

            foreach ($this->positions as $category) {

                $duration = $category->order_duration;

                $date_to = getDateTo($category->date_from, $category->order_type, $duration);

                $coords = explode(',', $this->coordinates);

                $branches->orWhereHas('machines', function (Builder $q) use (
                    $category,
                    $date_to,
                    $duration,
                    $coords
                ) {
                    $q->categoryBrandModel($category->type_id, $category->brand_id, $category->machinery_model_id)
                        ->whereInCircle($coords[0], $coords[1], true)
                        ->checkAvailable($category->date_from, $date_to, $category->order_type, $duration);
                }/*, '>=', $category->pivot->count*/);

            }
        });


        return $branches;
    }

    /* function scopeCanAccept($q, $user_id = null)
     {
         $user_id = $user_id ?: Auth::id();

         $user = User::query()->findOrFail($user_id);

         return $q->where('user_id', '!=', $user_id)->whereHas('positions', function ($q) use ($user){

             $q->whereIn('type_id', $user->machines()->pluck('type'));

         });
     }*/

    function scopeStartTomorrow(Builder $q)
    {
        $q->whereDoesntHave('orders')->whereBetween('start_date',
            [now()->addDay()->startOfDay(), now()->addDay()->endOfDay()]);
    }

    function isArchived()
    {
        return in_array($this->status, [Lead::STATUS_DONE, Lead::STATUS_CLOSE, Lead::STATUS_CANCEL]);
    }

    function fromDispatcher()
    {
        return $this->customer instanceof Customer;
    }

    function getContractors()
    {
        $branches = CompanyBranch::query()->where('id', '!=', $this->company_branch_id);

        $this->userAvailableQuery($branches);

        $branches = $branches->get();

        foreach ($branches as $branch) {

            $machines = collect();
            $ids = [];
            foreach ($this->positions as $category) {

                $duration = $category->order_duration;

                $date_to = getDateTo($category->date_from, $category->order_type, $duration);

                $vehicles =
                    $branch->machines()
                        ->categoryBrandModel($category->type_id, $category->brand_id, $category->machinery_model_id)
                        ->whereInCircle($this->coords['lat'], $this->coords['lng'])
                        ->checkAvailable($category->date_from, $date_to, $category->order_type, $duration)
                        /*->take($category->count)*/
                        ->whereNotIn('id', $ids)
                        ->get();
                /** @var Machinery $machine */
                foreach ($vehicles as $machine) {

                    $ids[] = $machine->id;

                    $coordinates =
                        $category->waypoints
                            ? "{$category->waypoints->coordinates->lat},{$category->waypoints->coordinates->lng}"
                            : $this->coordinates;
                    $order_type_duration = $category->waypoints
                        ? (
                        $machine->tariff_type === Tariff::TIME_CALCULATION
                            ? $category->order_duration
                            : round($category->waypoints->distance / 1000)
                        )
                        : $category->order_duration;
                    //    $distance = $machine->calculateDeliveryDistance($coordinates);

                    $cost = ($machine->calculateCost($category->order_type, $order_type_duration, $this->pay_type,
                        $category->params));
                    $machine->order_cost = round($cost['price']);
                    $machine->date_from = clone $category->date_from;
                    $machine->date_to = $date_to;
                    $machine->order_type = $category->order_type;
                    $machine->duration = $duration;

                    $machine->order_waypoints = $category->waypoints;
                    $machine->order_params = $category->params;

                }
                $machines = $machines->merge($vehicles);
            }

            $branch->machines = VehicleSearch::collection($machines);

        }

        $this->received_contractors = (clone $branches);

        return $branches;
    }

    function getVehiclesForLead($company_branch_id = null)
    {
        if (!Auth::check() && !$company_branch_id) {
            return [];
        }
        $company_branch_id =
            $company_branch_id
                ?: $this->company_branch_id;

        $company_branch = CompanyBranch::query()->find($company_branch_id);

        if ($company_branch) {

            $machines = collect();

            $coords = explode(',', $this->coordinates);

            $ids = [];
            foreach ($this->positions as $category) {

                $duration = $category->order_duration;

                $date_to = getDateTo($category->date_from, $category->order_type, $duration);


                $vehicles = $company_branch->machines()
                    ->categoryBrandModel($category->type_id, $category->brand_id, $category->machinery_model_id)
                    ->whereInCircle($coords[0], $coords[1])
                    ->checkAvailable($category->date_from, $date_to, $category->order_type, $duration)
                    ->whereNotIn('id', $ids)
                    /* ->take($category->pivot->count)*/
                    ->get();


                /** @var Machinery $machine */
                foreach ($vehicles as $machine) {
                    $ids[] = $machine->id;
                    $coordinates =
                        $category->waypoints
                            ? "{$category->waypoints->coordinates->lat},{$category->waypoints->coordinates->lng}"
                            : $this->coordinates;

                    $duration = $category->waypoints
                        ? (
                        $machine->tariff_type === Tariff::TIME_CALCULATION
                            ? $category->order_duration
                            : round($category->waypoints->distance / 1000)
                        )
                        : $category->order_duration;
                    $cost  = ($machine->calculateCost($category->order_type, $duration, $this->pay_type,
                            $category->params)) / 100;
                    $machine->order_cost = round($cost['price']);
                    //$distance = $machine->calculateDeliveryDistance($coordinates);

                    $machine->date_from = $category->date_from;
                    $machine->date_to = $date_to;
                    $machine->order_duration = $duration;
                    $machine->order_waypoints = $duration;
                    $machine->params = $category->params;
                }

                $machines = $machines->merge($vehicles);
            }

            /*   $machines = $machines->map(function ($machine) use ($user) {

                   $user_machine = $user->machines->where('id', $machine->id)->first();

                   $machine->order_cost = $user_machine->order_cost;


                   return $machine;
               });*/

            return VehicleSearch::collection($machines);

        }

        return [];
    }

    function canAccept($company_branch_id = null)
    {
        $company_branch_id =
            $company_branch_id
                ?: app()->make(RequestBranch::class)->companyBranch->id;

        $users = CompanyBranch::query()->whereId($company_branch_id);

        return $this->userAvailableQuery($users)->exists() && $this->status === self::STATUS_OPEN;

    }

    function getEstimatedCost($user_id = null)
    {
        if (Auth::check() && Auth::id() !== $this->user_id) {
            $user_id = Auth::id();
        }
        $users =
            $this->received_contractors
                ?: $this->getContractors();

        $user =
            $user_id
                ? $users->where('id', $user_id)->first()
                : $users->first();


        if ($user) {
            return $user->machines->sum('order_cost');
        }
        return null;
    }

    function getMyContractors(CompanyBranch $branch = null)
    {
        $company_id =
            $branch
                ? $branch->company_id
                : $this->company_branch->company_id;
        $contractors = Contractor::query()->forCompany($company_id);


        $contractors->where('city_id', $this->city_id);

        $contractors->where(function ($q) {

            foreach ($this->positions as $category) {
                $q->orWhereHas('vehicles', function ($q) use (
                    $category
                ) {
                    $q->where('type_id', $category->type_id);
                });
            }
        })->with([
            'vehicles' => function ($q) {
                $q->whereIn('type_id', $this->positions->pluck('type_id')
                    ->toArray());
            }
        ]);

        $contractors = $contractors->get();

        return $contractors;

    }


    function getMainContractorsAttribute()
    {
        return $this->getMainContractors();
    }

    function getDateToAttribute()
    {

        $positions = $this->positions->map(function ($position) {

            $position->end_date =
                strtotime((string) getDateTo($position->date_from, $position->order_type, $position->order_duration));

            return $position;
        });


        return Carbon::createFromTimestamp($positions->max('end_date'));

    }

    function getCoordsAttribute()
    {
        $coords = explode(',', $this->coordinates);

        return [
            'lat' => $coords[0],
            'lng' => $coords[1],
        ];
    }

    function scopeVisible($q)
    {
        $q->where(function ($q) {

            $q->forCompany()->orWhere('publish_type', self::PUBLISH_ALL_CONTRACTORS);
        });


    }

    function scopeForPeriod(
        Builder $q,
        Carbon $dateFrom,
        Carbon $dateTo
    ) {
        $dateFrom->startOfDay();
        $dateTo->endOfDay();

        return
            $q->whereHas('positions', function (Builder $q) use (
                $dateFrom,
                $dateTo
            ) {
                $q->selectRaw("(CASE 
                        WHEN lead_positions.order_type = 'shift'
                        THEN DATE_ADD(lead_positions.date_from, INTERVAL (lead_positions.order_duration - 1) DAY)
                         WHEN lead_positions.order_type ='hour'
                        THEN DATE_ADD(lead_positions.date_from, INTERVAL lead_positions.order_duration HOUR)
                        END) as date_end, date_from");
                $q->havingBetween('date_end', [$dateFrom, $dateTo])
                    ->orHavingRaw('(lead_positions.date_from BETWEEN ? AND ?)', [$dateFrom, $dateTo])
                    ->orHavingRaw('(lead_positions.date_from <= ? AND date_end >= ?)', [$dateFrom, $dateFrom])
                    ->orHavingRaw('(lead_positions.date_from <= ? AND date_end >= ?)', [$dateTo, $dateTo]);
            });

    }

    function scopeArchive($q)
    {
        return $q->whereIn('status',
            [Lead::STATUS_DONE, Lead::STATUS_CLOSE, Lead::STATUS_CANCEL, Lead::STATUS_EXPIRED]);
    }

    function scopeActive($q)
    {
        return $q->whereNotIn('status',
            [Lead::STATUS_DONE, Lead::STATUS_CLOSE, Lead::STATUS_CANCEL, Lead::STATUS_EXPIRED]);
    }

    /**
     * Скоуп заявок НЕ из выбранной компании
     * @param $q
     * @param  null  $company_id
     * @return mixed
     */
    function scopeWithoutCompany(
        $q,
        $company_id = null
    ) {
        $company_id =
            $company_id
                ?: app(RequestBranch::class)->company->id;
        return $q->whereDoesntHave('company_branch', function ($q) use (
            $company_id
        ) {
            $q->where('company_id', $company_id);
        });
    }

    /**
     * Скоуп заявок для выбранной компании
     * @param $q
     * @param  null  $company_id
     * @return mixed
     */
    function scopeForCompany(
        $q,
        $company_id = null
    ) {
        $company_id =
            $company_id
                ?: app(RequestBranch::class)->company->id;
        return $q->whereHas('company_branch', function ($q) use (
            $company_id
        ) {
            $q->where('company_id', $company_id);
        });
    }

    function scopeDispatcherLead($q)
    {
        return $q->where('customer_type', Customer::class);
    }

    function scopeClientLead($q)
    {
        return $q->where('customer_type', CompanyBranch::class);
    }

    function getDateAttribute()
    {
        return $this->start_date->format('Y-m-d');
    }

    function getTimeAttribute()
    {
        return $this->start_date->format('H:i');
    }

    function getFullAddressAttribute()
    {
        $region =
            $this->region
                ? "{$this->region->name},"
                : '';
        $city =
            $this->city
                ? "{$this->city->name},"
                : '';
        return trim("{$region} {$city} {$this->address}");
    }

    function getCurrencyAttribute()
    {
        return $this->company_branch->currency;
    }

    function isClosed()
    {
        return in_array($this->status, [
            self::STATUS_DONE,
            self::STATUS_CLOSE,
            self::STATUS_EXPIRED
        ]);
    }


    function scopeHasWorker(
        Builder $q,
        $workerId = null
    ) {
        if ($workerId) {
            $q->whereHas('orders', function ($orders) use (
                $workerId
            ) {
                $orders->hasWorker($workerId);
            });
        }
    }

    function generateCompanyLink(): string
    {
        /** @var CompanyBranch $branch */
        $branch = $this->company_branch;

        return $branch->getUrl("leads/{$this->id}/info");
    }

    function getLinkAttribute()
    {
        return "https://{$this->domain->url}/contractor/leads/{$this->id}/info";
    }

    /**
     * Создание диспетчерского заказа на исполнителя трансбазы
     * В заказе цена указывается диспетчером и не калькулирутеся системой.
     *
     * @param  CompanyBranch  $contractor
     * Массив с информацей о технике, стоимости, даты начала и продолжительности заказа.
     * @param $vehicles
     * @return $this
     */
    function createOrder(
        CompanyBranch $contractor,
        $vehicles
    ) {
        $collection = collect($vehicles);

        //Получение минимальной даты начала работ у техники.
        $date_from = Carbon::createFromTimestamp($collection->map(function ($v) {
            $v['date_from'] = strtotime($v['date_from']);
            return $v;
        })->min('date_from'));

        $vehicles = collect();

        //Проверка доступности техники исполнителя перед созданием заказа.
        foreach ($collection as $vehicle) {
            $df = Carbon::parse($vehicle['date_from']);

            $date_to = getDateTo($df, $vehicle['order_type'], $vehicle['order_duration']);

            $vehicles->push($contractor->machines()
                ->whereInCircle($this->coords['lat'], $this->coords['lng'])
                // ->checkAvailable($df, $date_to, $vehicle['order_type'], $vehicle['order_duration'])
                ->findOrFail($vehicle['id']));
        }

        $orderService = new OrderManagement([], $this->coordinates);

        $orderService
            ->setInitiator(Auth::user())
            ->setCustomer($this->company_branch)
            ->setContractor($contractor)
            ->setDispatcherOrder()
            ->setVehicles($vehicles)
            ->setDateFrom($date_from)
            ->setAmount(
                numberToPenny($collection->sum('order_cost') + $collection->sum('value_added'))
            )
            ->setDetails([
                'contact_person' => $this->customer_name,
                'address' => $this->address,
                'comment' => $this->comment,
                'region_id' => $this->city
                    ? $this->city->region->id
                    : null,
                'city_id' => $this->city
                    ? $this->city->id
                    : null,
                'coordinates' => $this->coordinates,
                'start_time' => $date_from->format('H:i'),
            ])
            ->createDispatcherOder();

        //Привзяывае технику к Заказу
        foreach ($collection as $item) {

            $vehicle = $vehicles->where('id', $item['id'])->first();

            $position = $this->positions->where('type_id', $vehicle->type)->first();
            $orderService->created_proposal
                ->attachCustomVehicles(
                    $item['id'],
                    numberToPenny($item['order_cost']),
                    Carbon::parse($item['date_from']),
                    $item['order_type'],
                    $item['order_duration'],
                    0,
                    0,
                    $position->waypoints,
                    $position->params
                );
        }
        $this->audits()->update([
            'auditable_type' => Order::class,
            'auditable_id' => $orderService->created_proposal->id,
        ]);

        Payment::create([
            'system' => 'dispatcher',
            'status' => Payment::STATUS_WAIT,
            'currency' => RequestHelper::requestDomain()->currency->code,
            'amount' => $orderService->created_proposal->amount,
            'company_branch_id' => $orderService->customerCompanyBranch->id,
            'creator_id' => Auth::id(),
            'order_id' => $orderService->created_proposal->id
        ]);

        $this->orders()->attach($orderService->created_proposal->id);

        $orderService->created_proposal->accept();

        return $orderService->created_proposal;
    }

    function isVatSystem()
    {
        return $this->company_branch->getSettings()->price_without_vat && $this->contractorRequisite && $this->contractorRequisite->vat_system === Price::TYPE_CASHLESS_VAT;
    }

    /**
     * Создание диспетчерского заказа на собственную технику диспетчера или его подрядчиков
     * @param $params
     * @return Order
     */
    function createMyOrder(
        $params,
        $contractorRequisite = null,
        $contract = null,
        $shift_duration = null
    ) {

        $params = collect($params);

        /** Минимальная дата в заявке.
         * @var Carbon $date_from
         */
        $date_from = Carbon::createFromTimestamp($params->map(function ($cart_item) {

            $position = $this->positions->where('id', $cart_item['position_id'])->first();

            $cart_item['start_date'] = $position->date_from->getTimestamp();
            return $cart_item;
        })->min('start_date'));


        $orderManage = new OrderManagement([], $this->coordinates);

        /** Создание заказа */
        $orderManage
            ->setInitiator($this->manager)
            /* ->setAmount(numberToPenny(
                 $params->sum('amount')
                 + $params->sum('value_added')
                 +  $params->sum('delivery_cost')
                 +  $params->sum('return_delivery')
             ))*/
            ->setCustomer($this->company_branch)
            ->setContractor($this->company_branch)
            ->setDispatcherCustomer($this->customer)
            ->setContractorRequisites($contractorRequisite)
            ->setDateFrom($date_from)
            ->setDispatcherOrder()
            ->setDetails([
                'contact_person' => $this->customer_name,
                'address' => $this->address,
                'region_id' => $this->region_id,
                'city_id' => $this->city_id,
                'coordinates' => $this->coordinates,
                'start_time' => $date_from->format('H:i'),
            ])
            ->createDispatcherOder();

        /** @var Order $order */
        $order = $orderManage->created_proposal;

        if ($this->documentsPack) {
            $order->documentsPack()->associate($this->documentsPack);
        }

        if(!$contract) {
            $contract = $this->customer->contracts
                ->where('requisite_id', $contractorRequisite->id)
                ->where('requisite_type',  get_class($contractorRequisite))->first() ?: $this->customer->generateContract($contractorRequisite);
        }

        /** Привзяка техники из корзины */

        foreach ($params as $item) {
            $position = $this->positions->where('id', $item['position_id'])->first();

            $date_to = getDateTo($position->date_from, $position->order_type, $position->order_duration);
            if ($item['type'] === 'vehicle') {


                $vehicle = $this->company_branch->machines()
                    ->categoryBrandModel($position->type_id, $position->brand_id, $position->machinery_model_id);

                if ($position->order_type === TimeCalculation::TIME_TYPE_HOUR) {

                    $vehicle->checkAvailable($position->date_from,
                        getDateTo($position->date_from, $position->order_type, $position->order_duration),
                        $position->order_type, $position->order_duration);
                }

                $vehicle = $vehicle->find($item['id']);
                if (!$vehicle) {
                    throw ValidationException::withMessages([
                        'errors' => ["Техника занята в этот период"]
                    ]);
                }

                $costPerUnit = $item['cost_per_unit'];
                $deliveryCost = ($item['delivery_cost'] ?? 0);
                $returnDeliveryCost = ($item['return_delivery'] ?? 0);

                $component = $order->attachCustomVehicles($vehicle->id,
                    numberToPenny($costPerUnit),
                    $position->date_from,
                    $position->order_type,
                    $position->order_duration,
                    numberToPenny($deliveryCost),
                    numberToPenny($returnDeliveryCost),
                    $position->waypoints,
                    $position->params,
                    /**  Последнее айди приложения к договору для текущего клиента.*/
                    $this->customer->getAndIncrementApplicationId($contract),
                    ($item['comment'] ?? ''),
                    shift_duration: $item['shift_duration'] ?? null
                );
                $cashlessType = $item['cashless_type'] ?? false;
                if ($cashlessType) {
                    $component->cashless_type = $cashlessType;
                    $component->save();
                }
                $isMonth = toBool($item['is_month'] ?? null);
                if($isMonth) {
                    $component->is_month = true;
                    $component->month_duration = $item['month_duration'];
                    $component->save();
                }
                //Добавленая стоимость для подрядчика
                if ($vehicle->subOwner) {
                    $valueAdded = numberToPenny($item['value_added']);
                    if ($order->company_branch->getSettings()->price_without_vat && $this->contractorRequisite && $this->contractorRequisite->vat_system === Price::TYPE_CASHLESS_VAT) {
                        $valueAdded = Price::addVat($valueAdded, $order->company_branch->domain->country->vat);
                    }
                    $component->value_added =$valueAdded;
                    $component->save();
                }


                if ($item['order_type'] === 'warm') {
                    $driver =
                        CompanyWorker::query()->whereType(CompanyWorker::TYPE_DRIVER)->findOrFail($item['company_worker_id']);
                    $component->driver()->associate($driver);
                    $component->save();
                };
                $isVatSystem =
                    $this->company_branch->getSettings()->price_without_vat && $this->contractorRequisite && $this->contractorRequisite->vat_system === Price::TYPE_CASHLESS_VAT;

                $schedule = $vehicle->getScheduleForDay($position->date_from->format('D'));

                MachineryStamp::createTimestamp($vehicle->id, $order->id, 'on_the_way',
                    $position->date_from, $order->coordinates);

                if (!empty($item['services'])) {
                    foreach ($item['services'] as $service) {
                        $component->services()->save(new OrderComponentService([
                            'price' => Price::addVat(numberToPenny($service['price']), $isVatSystem
                                ? $this->company_branch->domain->vat
                                : 0),
                            'name' => $service['name'],
                            'count' => $service['count'] ?? 1,
                            'value_added' => Price::addVat(numberToPenny($service['value_added'] ?? 0), $isVatSystem
                                ? $this->company_branch->domain->vat
                                : 0),
                            'custom_service_id' => $service['id'],
                        ]));
                    }
                }
                if (!empty($item['parts'])) {

                    foreach ($item['parts'] as $part) {

                        $stock = Stock::query()->forBranch()->findOrFail($part['stock_id']);
                        $serialAccounting = toBool($part['serial'] ?? false);

                        /** @var Item $item */
                        $item = new Item([
                            'part_id' => $part['part_id'],
                            'stock_id' => $stock->id,
                            'unit_id' => $part['unit_id'],
                            'cost_per_unit' => numberToPenny($part['cost_per_unit']),
                            'amount' => $serialAccounting
                                ? 0
                                : $part['amount'],
                            'serial_accounting' => $serialAccounting,
                            'company_branch_id' => $this->company_branch->id,
                        ]);
                        $item->owner()->associate($component);
                        $item->save();
                        if ($serialAccounting) {

                            foreach ($part['serial_numbers'] as $number) {
                                $item->serialNumbers()->save(new ItemSerial(['serial' => $number['serial']]));
                            }
                            $item->update([
                                'amount' => $item->serialNumbers()->count()
                            ]);
                        }


                    }
                }
            } elseif ($item['type'] === 'warehouse_set') {

                $warehouseSet = WarehousePartSet::query()->findOrFail($position->warehouse_part_set_id);
                $parts = collect($item['parts']);
                $parts = $parts->map(function ($p) {
                    $p['sum'] = $p['cost_per_unit'] * $p['amount'];
                    return $p;
                });
                $costPerUnit = $parts->sum('sum');
                $deliveryCost = ($item['delivery_cost'] ?? 0);
                $returnDeliveryCost = ($item['return_delivery'] ?? 0);
                $component = $order->attachCustomVehicles($warehouseSet->id,
                    numberToPenny($costPerUnit),
                    $position->date_from,
                    $position->order_type,
                    $position->order_duration,
                    numberToPenny($deliveryCost),
                    numberToPenny($returnDeliveryCost),
                    $position->waypoints,
                    $position->params,
                /**  Последнее айди приложения к договору для текущего клиента.*/
                    $this->customer->getAndIncrementApplicationId($contract),
                    ($item['comment'] ?? ''),
                    'warehouse_set'
                );

                $cashlessType = $item['cashless_type'] ?? false;
                if ($cashlessType) {
                    $component->cashless_type = $cashlessType;
                    $component->save();
                }
                //Добавленая стоимость для подрядчика
                if ($warehouseSet->subOwner) {
                    $component->value_added = numberToPenny($item['value_added']);
                    $component->save();
                }


                if ($item['order_type'] === 'warm') {
                    $driver =
                        CompanyWorker::query()->whereType(CompanyWorker::TYPE_DRIVER)->findOrFail($item['company_worker_id']);
                    $component->driver()->associate($driver);
                    $component->save();
                };

                MachineryStamp::createTimestamp($warehouseSet->id, $order->id, 'on_the_way',
                    "{$position->date_from->format('Y-m-d H:i:s')}", $order->coordinates, WarehousePartSet::class);
                if (!empty($item['parts'])) {

                    foreach ($item['parts'] as $part) {

                        $cbwp = CompanyBranchWarehousePart::query()->where([
                            'part_id' => $part['part_id'],
                            'company_branch_id' => $this->company_branch->id,
                        ])->firstOrFail();

                        $warehouseSet->parts()->attach($cbwp->id, ['count' => $part['amount']]);
                        WarehousePartsOperation::query()->create([
                            'company_branches_warehouse_part_id' => $cbwp->id,
                            'order_worker_id' => $component->id,
                            'type' => 'rent',
                            'count' => $part['amount'],
                            'cost_per_unit' => numberToPenny($part['cost_per_unit']),
                            'begin_date' => $component->date_from,
                            'end_date' => $component->date_to,
                        ]);

                    }
                }
            }


            $order->types()->attach($position->type_id, ['brand_id' => 0, 'comment' => ('')]);

        }


        Payment::create([
            'system' => 'dispatcher',
            'status' => Payment::STATUS_WAIT,
            'currency' => RequestHelper::requestDomain()->currency->code,
            'amount' => $order->amount,
            'creator_id' => Auth::id(),
            'company_branch_id' => $order->company_branch_id,
            'order_id' => $order->id
        ]);
        /** Привзяка контактов к заказу */

      /// $contact = (new User\IndividualRequisite([
      ///     'firstname' => $this->customer_name,
      ///     'type' => User\IndividualRequisite::TYPE_PERSON,
      ///  //   'position' => trans('calls/calls.main'),
      ///     'company_branch_id' => $this->company_branch_id,
      /// ]));
      /// $order->contacts()->save($contact);
      /// $contact->phones()->save(new ContactPhone(['phone' => $this->phone]));
      /// $contact->emails()->save(new ContactEmail(['email' => $this->email]));

//        if ($this->contacts->isNotEmpty()) {
//            /** @var Collection $contacts */
//            $this->contacts->each(function ($item) use (
//                $order
//            ) {
//                $item = collect($item)->except('id')->toArray();
//                $contact = (new Contact($item));
//                $order->contacts()->save($contact);
//                foreach ($item['emails'] as $email) {
//                    $contact->emails()->save(new ContactEmail([
//                        'email' => (is_array($email)
//                            ? $email['email']
//                            : $email)
//                    ]));
//                }
//                foreach ($item['phones'] as $phone) {
//                    $contact->phones()->save(new ContactPhone([
//                        'phone' => (is_array($phone)
//                            ? $phone['phone']
//                            : $phone)
//                    ]));
//                }
//            });
//        }
        $this->orders()->attach($order->id);
        $order->accept();

        if($this->positions()->sum('count') <= $order->components->count()) {
            $this->update([
                'tmp_status' => Lead::STATUS_DONE
            ]);
        }


        $invoice = [
            'type' => 'time_calculation',
            'use_oneC' => 0,
            'owner_id' => $order->id,
            'owner_type' => 'order',
            'items' => []
        ];

        /** Создание документа приложения к каждой позиции */
        foreach ($order->components as $k => $position) {
            /* if ($position->worker instanceof Machinery) {
                 (new OrderDocumentService(['position_id' => $position->id]))->generateApplication($order);
             }*/
            $invoice['items'][] = [
                'id' => $position->id,
                'order_duration' => $position->order_duration,
            ];
        }
        try {
            if ($order->company_branch->ins_setting && $order->company_branch->ins_setting->active) {
                $insuranceService = new InsuranceService();
                $scoring = $insuranceService->getScoring($order);
                if ($scoring['result']->final_result == 0) {
                    foreach ($order->components as $component) {
                        $insuranceService->createInsuranceCertificate($component, $scoring['type']);
                    }
                }
            }
        } catch (Exception $e) {
            Log::error('Failed to create certificate', [
                'exception' => $e->getMessage().'File: '.$e->getFile().' Line: '.$e->getLine()
            ]);
            Log::error('Data', [
                'legalRequisites' => $order->customer->legal_requisites,
                'individualRequisites' => $order->customer->individual_requisites
            ]);
        } catch (TypeError $e) {
            Log::error('Failed to create certificate', [
                'exception' => $e->getMessage().'File: '.$e->getFile().' Line: '.$e->getLine()
            ]);
            Log::error('Data', [
                'legalRequisites' => $order->customer->legal_requisites,
                'individualRequisites' => $order->customer->individual_requisites
            ]);
        }
        // try {

        //     $request = new Request($invoice);

        //     $c = new InvoiceController($request);
        //     $c->store($request);
        // } catch (\Exception $e) {

        // }


        return $order;
    }

    function setExpired()
    {
        $this->update(['status' => self::STATUS_EXPIRED]);
        return $this;
    }


    function getDispatcherSumAttribute()
    {
        $item =
            $this->order
                ? $this->order
                : ($this->dispatcher_order
                ? $this->dispatcher_order
                : false);

        return $item
            ? $item->amount
            : 0;
    }


    function scopeForDomain(
        $q,
        $domain_id = null
    ) {
        $domain_id =
            $domain_id
                ?: RequestHelper::requestDomain('id');

        if (!$domain_id) {
            return $q;
        }
        return $q->whereDomainId($domain_id);

    }

    function getContractorRequisiteTypeId()
    {

        return $this->contractorRequisite
            ?
            ($this->contractorRequisite instanceof User\IndividualRequisite
                ? "individual_{$this->contractorRequisite->id}"
                : "legal_{$this->contractorRequisite->id}")
            : '';
    }
}
