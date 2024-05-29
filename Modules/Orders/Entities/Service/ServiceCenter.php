<?php

namespace Modules\Orders\Entities\Service;

use App\Machinery;
use App\User\BankRequisite;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use App\Overrides\Model;
use Modules\CompanyOffice\Entities\Company\DocumentsPack;
use Modules\CompanyOffice\Entities\Company\GoogleEvent;
use Modules\CompanyOffice\Services\BelongsToCompanyBranch;
use Modules\CompanyOffice\Services\HasContacts;
use Modules\CompanyOffice\Services\HasManager;
use Modules\CompanyOffice\Services\InternalNumbering;
use Modules\ContractorOffice\Entities\CompanyWorker;
use Modules\ContractorOffice\Entities\Services\CustomService;
use Modules\ContractorOffice\Entities\Vehicle\MachineryBase;
use Modules\ContractorOffice\Entities\Vehicle\TechnicalWork;
use Modules\Dispatcher\Entities\Customer\CustomerContract;
use Modules\Dispatcher\Entities\DispatcherInvoice;
use Modules\Orders\Entities\OrderDocument;
use Modules\Orders\Services\OrderTrait;
use Modules\PartsWarehouse\Entities\Stock\Item;

class ServiceCenter extends Model
{

    use BelongsToCompanyBranch, InternalNumbering, HasContacts, HasManager, OrderTrait;

    protected $fillable = [
        'name',
        'type',
        'description',
        'note',
        'phone',
        'date',
        'status',
        'contact_person',
        'company_branch_id',
        'base_id',
        'machinery_id',
        'creator_id',
        'date_from',
        'documents_pack_id',
        'date_to',
        'status_tmp',
        'bank_requisite_id',
        'is_plan',
        'address',
        'comment',
        'address_type',
        'is_warranty',
        'client_vehicles',
        'contract_id'
    ];

    const STATUS_NEW = 'new';
    const STATUS_ACCEPT = 'accept';
    const STATUS_IN_PROGRESS = 'in_progress';
    const STATUS_WAIT_PARTS = 'wait_parts';
    const STATUS_DONE = 'done';
    const STATUS_ISSUED = 'issued';
    const STATUS_COORDINATION = 'coordination';

    public static $statuses = [
        self::STATUS_NEW,
        self::STATUS_ACCEPT,
        self::STATUS_IN_PROGRESS,
        self::STATUS_WAIT_PARTS,
        self::STATUS_DONE,
        self::STATUS_ISSUED,
        self::STATUS_COORDINATION,
    ];

    public function getStatusNameAttribute()
    {
        $status = $this->status;

        if (!$status) {
            $status = $this->status_tmp;
        }

        switch ($status) {
            case self::STATUS_NEW:
                return 'Новый';
            case self::STATUS_ACCEPT:
                return 'Принят';
            case self::STATUS_IN_PROGRESS:
                return 'В работе';
            case self::STATUS_WAIT_PARTS:
                return 'Ожидание запчастей';
            case self::STATUS_DONE:
                return 'Работы выполнены';
            case self::STATUS_ISSUED:
                return 'Выдан';
            case self::STATUS_COORDINATION:
                return 'Согласование';
            default:
                return '';
        }
    }

    protected $with = ['customer', 'machinery', 'workers', 'order'];

    protected $dates = [
        'date_from',
        'date_to'
    ];

    protected $casts = [
        'is_plan' => 'boolean',
        'is_warranty' => 'boolean',
        'client_vehicles' => 'array',
    ];

    protected $appends = ['time_from', 'time_to'];

    function getTimeFromAttribute()
    {
        return $this->date_from?->format('H:i');
    }

    function getTimeToAttribute()
    {
        return $this->date_to?->format('H:i');
    }

    function getNameAttribute($val)
    {
        if (!$val) {
            $customer = $this->customer?->company_name;

            $val =
                "Заказ-наряд {$this->internal_number}".($customer
                    ? " - {$customer}"
                    : '');
        }
        return $val;
    }

    public function google_event()
    {
        return $this->morphOne(GoogleEvent::class, 'eventable');
    }

    function customer()
    {
        return $this->morphTo();
    }

    function machinery()
    {
        return $this->belongsTo(Machinery::class, 'machinery_id');
    }

    function workers()
    {
        return $this->belongsToMany(CompanyWorker::class, 'service_centers_mechanics');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\MorphMany
     */
    function invoices()
    {
        return $this->morphMany(DispatcherInvoice::class, 'owner');
    }

    function oldWorks()
    {
        return $this->belongsToMany(ServiceWork::class, 'service_works_center')->withPivot([
            'price',
            'count',
        ]);
    }

    function works()
    {
        return $this->belongsToMany(CustomService::class, 'service_center_custom_services')->withPivot([
            'price',
            'count',
            'comment',
        ]);
    }

    function worksPivot()
    {
        return $this->hasMany(ServiceCenterWorksPivot::class);
    }

    function parts()
    {
        return $this->morphMany(Item::class, 'owner')->with('audits.user');
    }

    function order()
    {
        return $this->morphTo();
    }

    function documentsPack()
    {
        return $this->belongsTo(DocumentsPack::class, 'documents_pack_id');//->select('id', 'name', 'type_from', 'type_to');
    }

    function contractorRequisite()
    {
        return $this->morphTo();
    }

    function base()
    {
        return $this->belongsTo(MachineryBase::class, 'base_id');
    }

    function scopeWithPaidInvoiceSum(Builder $builder)
    {
        return $builder->withCount([
            'invoices as paid_sum' => function ($q) {
                $q->select(\DB::raw("SUM(`paid_sum`)"));
            }
        ]);
    }

    function getPreparedSumAttribute()
    {
        return $this->total_works_sum + $this->total_parts_sum;
    }

    function getTotalWorksSumAttribute()
    {
        return $this->worksPivot->sum(fn($work) => $work->price * 100 * $work->count);
    }

    function getTotalPartsSumAttribute()
    {
        return $this->parts->sum(fn($part) => $part->amount * $part->cost_per_unit);
    }

    function scopeWithInvoiceSum(Builder $builder)
    {
        return $builder->withCount([
            'invoices as invoice_sum' => function ($q) {
                $q->select(\DB::raw("SUM(`sum`)"));
            }
        ]);
    }

    function documents()
    {
        return $this->morphMany(OrderDocument::class, 'order');
    }

    function technicalWork()
    {
        return $this->hasOne(TechnicalWork::class);
    }

    public function contract()
    {
        return $this->belongsTo(CustomerContract::class, 'contract_id');
    }

    public function bankRequisite()
    {
        return $this->belongsTo(BankRequisite::class);
    }

    function scopeForPeriod($q, Carbon $dateFrom, Carbon $dateTo, $setAllDay = true)
    {
        if($setAllDay) {
            $dateFrom->startOfDay();
            $dateTo->endOfDay();
        }

        return $q->where(function (Builder $q) use ($dateFrom, $dateTo) {
            $q->where(function ($q) use ($dateFrom) {
                $q->where('date_from', '<=', $dateFrom);
                $q->where('date_to', '>=', $dateFrom);

            })->orWhere(function ($q) use ($dateTo) {
                $q->where('date_from', '<=', $dateTo);
                $q->where('date_to', '>=', $dateTo);
            })
                ->orWhereBetween('date_from', [$dateFrom, $dateTo])
                ->orWhereBetween('date_to', [$dateFrom, $dateTo]);

        });

    }
}
