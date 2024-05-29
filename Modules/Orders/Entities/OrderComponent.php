<?php

namespace Modules\Orders\Entities;

use App\Machinery;
use App\Machines\FreeDay;
use App\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use App\Overrides\Model;
use Modules\CompanyOffice\Entities\Company\InsCertificate;
use Modules\ContractorOffice\Entities\CompanyWorker;
use Modules\ContractorOffice\Entities\Vehicle\MachineryBase;
use Modules\ContractorOffice\Entities\Vehicle\Price;
use Modules\ContractorOffice\Services\Tariffs\TimeCalculation;
use Modules\Dispatcher\Entities\ContractorPay;
use Modules\Dispatcher\Entities\DispatcherInvoice;
use Modules\Orders\Entities\Service\ServiceCenter;
use Modules\PartsWarehouse\Entities\Stock\Item;
use Modules\PartsWarehouse\Entities\Warehouse\WarehousePartsOperation;
use OwenIt\Auditing\Contracts\Auditable;
use OwenIt\Auditing\Auditable as Audit;

class OrderComponent extends Model implements Auditable
{
    use Audit;

    protected $table = 'order_workers';

    /**
     *
     */
    const STATUS_ACCEPT = 'accept';
    const STATUS_DONE = 'done';
    const STATUS_REJECT = 'reject';

    protected $fillable = [
        'category_id',
        'cashless_type',
        'order_id',
        'amount',
        'cost_per_unit',
        'date_from',
        'date_to',
        'delivery_cost',
        'order_type',
        'order_duration',
        'shift_duration',
        'waypoints',
        'regional_representative_commission',
        'regional_representative_id',
        'params',
        'application_id',
        'contractor_application_id',
        'complete',
        'status',
        'comment',
        'return_delivery',
        'finish_date',
        'company_worker_id',
        'value_added',
        'machinery_base_id',
        'machinery_sets_order_id',
        'udp_number',
        'reject_type',
        'udp_date',
        'insurance_cost_per_unit',
        'insurance_cost',
        'description',
        'is_month',
        'month_duration',
        'avito_ad_sum',
        'avito_dotation_sum',
    ];

    private $loadedOrder;
    private $contractorRequisite;
    private $subContractorCalculation = false;

    public $company_branch;

    protected $auditInclude = [
        'amount',
        'date_from',
        'date_to',
        'delivery_cost',
        'order_type',
        'order_duration',
    ];

    protected $casts = [
        'waypoints' => 'object',
        'params' => 'object',
        'is_month' => 'boolean',
        'complete' => 'boolean',
        'date_from' => 'datetime',
        'date_to' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    //  protected $with = ['worker', 'histories', 'idle_periods', 'driver', 'media', 'services', 'parts'];

    protected $dates = [ 'finish_date', 'udp_date'];

    protected $appends = [
        'timestamps', 'total_sum', 'order_internal_number', 'is_paid',
        'cost_per_unit_without_vat', 'delivery_cost_without_vat',
        'return_delivery_without_vat',
        'value_added_without_vat',
        'amount_without_vat',
        'total_sum_without_vat',
        'actual_done',
        'service_center',
        'parts_sum',
        'services_sum_value_added',
    ];

    protected static function boot()
    {
        parent::boot();

        self::creating(function (self $model) {
            if(!$model->shift_duration){
                $model->shift_duration = $model->worker?->shift_duration;
            }
            if ($model->company_worker_id) {
                $model->generateReports();
            }
        });

        self::updating(function (self $model) {
            if(!$model->shift_duration){
                $model->shift_duration = $model->worker?->shift_duration;
            }
            if ($model->company_worker_id) {
                $model->generateReports();
            }            // $model->updateDateTo();
        });
    }

    function getOrderInternalNumberAttribute()
    {
        return Order::query()->whereId($this->order_id)->pluck('internal_number')->toArray()[0] ?? null;
    }

    function getOrderExternalIdAttribute()
    {
        return Order::query()->whereId($this->order_id)->pluck('external_id')->toArray()[0] ?? null;
    }

    function scopeAccepted($q)
    {
        return $q->where('status', '!=', Order::STATUS_REJECT);
    }

    function worker()
    {
        return $this->morphTo();
    }

    public function ins_certificates()
    {
        return $this->hasMany(InsCertificate::class,'order_worker_id');
    }

    public function ins_certificate()
    {
        return $this->hasOne(InsCertificate::class,'order_worker_id')->where('status', 1);
    }

    function machineryBase()
    {
        return $this->belongsTo(MachineryBase::class, 'machinery_base_id');
    }

    function driver()
    {
        return $this->belongsTo(CompanyWorker::class, 'company_worker_id');
    }

    function order()
    {
        return $this->belongsTo(Order::class, 'order_id');
    }

    function reports()
    {
        return $this->hasMany(OrderComponentReport::class, 'order_worker_id');
    }

    function reportsTimestamps()
    {
        return $this->hasManyThrough(OrderComponentReportTimestamp::class, OrderComponentReport::class,
            'order_worker_id', 'order_worker_report_id');
    }

    function services()
    {
        return $this->hasMany(OrderComponentService::class);
    }

    function serviceCenter()
    {
        return $this->morphOne(ServiceCenter::class, 'order');
    }

    function regional_representative()
    {
        return $this->belongsTo(User::class, 'regional_representative_id');
    }

    function contractorPays()
    {
        return $this->hasMany(ContractorPay::class, 'order_worker_id');
    }

    /**
     * Период простоя техники
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    function idle_periods()
    {
        return $this->hasMany(OrderComponentIdle::class, 'order_worker_id');
    }

    function getServiceCenterAttribute()
    {
        return \DB::table('service_centers')->where('order_id', $this->id)->select('id')->first();
    }

    /**
     * История изменений воркера
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    function histories()
    {
        return $this->hasMany(OrderComponentHistory::class, 'order_worker_id');
    }

    function getTimestampsAttribute()
    {
        return $this->worker instanceof Machinery
            ?
            $this->worker->order_timestamps()->where('order_id', $this->order_id)->get()
            : [];
    }

    function parts()
    {
        return $this->morphMany(Item::class, 'owner');
    }

    function rent_parts()
    {
        return $this->hasMany(WarehousePartsOperation::class, 'order_worker_id');
    }

    function getTotalSumAttribute()
    {
        return $this->amount + $this->delivery_cost + $this->return_delivery + $this->insurance_cost;
    }

    function getInsuranceCostAttribute($val)
    {
        return $val ? (float) $val : $val;
    }


    function getAmountAttribute($val)
    {
        $actual = ($this->cost_per_unit + ($this->subContractorCalculation ? 0 : $this->value_added)) * $this->order_duration;
        //$actual += $this->insurance_cost;
        if (!$val && $actual && $this->id && !$this->subContractorCalculation) {
            if ($this->exists) {
                static::query()->where('id', $this->id)->update(['amount' => $actual]);
            }
            /// $this->update(['amount' => $actual]);

            $val = $actual;
        }
        return $this->subContractorCalculation ? $actual : $val;
    }

    function getCostPerUnitDocAttribute()
    {
        return $this->cost_per_unit + ($this->subContractorCalculation ? 0 : $this->value_added);
    }


    function updateDateTo()
    {
        $val = $this->date_to;
        $dt = getDateTo($this->date_from, $this->order_type, $this->order_duration);

        if ($val->eq($dt)) {
            return $val;
        }
        $this->update([
            'date_to' => $dt
        ]);

        return $dt;
    }

    function scopeForPeriod(
        $q,
        Carbon $dateFrom,
        Carbon $dateTo,
        $setAllDay = false
    ) {
        if ($setAllDay) {
            $dateFrom->startOfDay();
            $dateTo->endOfDay();
        }
        //  $dateTo = $dateTo->toDateTimeString();
        //  $dateFrom = $dateFrom->toDateTimeString();
        return $q->where(function (Builder $q) use ($dateFrom, $dateTo) {
            $q->orWhere(function ($q) use ($dateFrom) {
                $q->where('date_from', '<=', $dateFrom);
                $q->where('date_to', '>=', $dateFrom);

            })->orWhere(function ($q) use (
                $dateTo
            ) {
                $q->where('date_from', '<=', $dateTo);
                $q->where('date_to', '>=', $dateTo);
            })
                ->orWhereBetween('date_from', [$dateFrom, $dateTo])
                ->orWhereBetween('date_to', [$dateFrom, $dateTo]);

        });

    }

    function generateReports()
    {
        $dates = $this->reportsTimestamps()->pluck('date')->map(function ($dt) {
            return $dt->format('Y-m-d');
        })->toArray();

        $diff =
            $this->order_type === TimeCalculation::TIME_TYPE_SHIFT
                ? $this->order_duration
                : 1;
        $days = (int) ceil($diff / 10);

        for ($i = 0; $i < $days; ++$i) {

            $this->createOrUpdateReport($i, $dates);
        }
    }

    function invoices()
    {
        return $this->belongsToMany(DispatcherInvoice::class, 'invoice_application_pivot', 'order_component_id',
            'invoice_id')->withPivot([
            'order_duration',
            'order_type',
            'cost_per_unit',
            'delivery_cost',
            'value_added',
            'return_delivery'
        ]);
    }

    function media()
    {
        return $this->morphMany(OrderMedia::class, 'owner');
    }

    function actual()
    {
        return $this->hasOne(OrderComponentActual::class);
    }

    private function createOrUpdateReport(
        $index,
        $existsDates = []
    ) {
        /** @var Carbon $startDate */
        $startDate = $this->date_from->copy()->addDays($index * 10);

        $stamp = $this->reportsTimestamps()->whereDate('date', $startDate)->first();

        /** @var OrderComponentReport $report */
        $report = $stamp
            ? $stamp->orderWorkerReport
            : $this->reports()->save(new OrderComponentReport([
                'worker_type' => $this->worker_type,
                'worker_id' => $this->worker_id,
            ]));

        $timestampsCount = $report->reportTimestamps()->count();

        for ($i = $timestampsCount; $i < 10; ++$i) {
            $date = $startDate->copy()->addDays($i);
            if (in_array($date->format('Y-m-d'), $existsDates)) {
                continue;
            }
            if ($date->gt($this->date_to)) {
                break;
            }

            $report->reportTimestamps()->save(new OrderComponentReportTimestamp([
                'date' => $date->format('Y-m-d'),
                'cost_per_unit' => ($this->order_type === TimeCalculation::TIME_TYPE_HOUR
                    ? $this->cost_per_unit
                    : $this->worker->sum_hour)
            ]));
        }
    }

    function calendar()
    {
        return $this->hasMany(FreeDay::class, 'order_component_id');
    }


    function getContractorSum()
    {
        return $this->amount - $this->value_added + $this->services->sum(function ($service) {
            return $service->count * ($service->price + $service->value_added);
        });
    }

    function getActualDoneAttribute()
    {
        $max = $this->calendar()->max('endDate');
        if ($max) {
            if($this->actual) {
                return $this->actual->order_duration;
            }
            $date = Carbon::parse($max);
            if (now()->gt($date)) {
                return $this->order_duration;
            } else {
                //  $count = $this->calendar()->forPeriod($this->date_from, now())->count();

                return $this->order_type === TimeCalculation::TIME_TYPE_SHIFT
                    ? $this->worker->getDurationForDates($this->date_from, now(), [$this->id])
                    : now()->diffInHours($this->date_from);
            }
        }

        return 0;
    }

    function getContractorPaidSum()
    {
        return $this->contractorPays()->sum('sum');
    }

    function getIsPaidAttribute()
    {
        return $this->getContractorSum() <= $this->getContractorPaidSum();
    }

    function getContractorRequisite()
    {
        if ($this->contractorRequisite) {
            return $this->contractorRequisite;
        }

        $order = Order::query()->setEagerLoads([])->find($this->order_id);
        $this->contractorRequisite = $order->contractorRequisite;
        $this->company_branch = $order->company_branch;

        return $this->contractorRequisite;
    }

    function getServicesSumAttribute()
    {
        return $this->services->sum(function ($service) {
            return $service->count * ($service->price + ($this->subContractorCalculation ? 0 : $service->value_added));
        });
    }

    function getServicesSumValueAddedAttribute()
    {
        return $this->services->sum(function ($service) {
            return $service->count * $service->value_added;
        });
    }

    function getServicesSumWithoutPledgeAttribute()
    {
        return $this->services->filter(fn($service) => !$service->customService->is_pledge)->sum(function ($service) {
            return $service->count * ($service->price + ($this->subContractorCalculation ? 0 : $service->value_added));
        });
    }

    function getTotalSumWithServicesWithoutPledgeAttribute()
    {
        return $this->total_sum + $this->services_sum_without_pledge;
    }

    function getTotalSumWithServicesWithoutPledgeWithoutVatAttribute()
    {
        return Price::removeVat($this->total_sum_with_services_without_pledge,
            ($this->getContractorRequisite() && $this->contractorRequisite->vat_system === Price::TYPE_CASHLESS_VAT
                ? $this->company_branch->domain->vat
                : 0));
    }

    function getTotalSumWithServicesAttribute()
    {
        return $this->total_sum + $this->services_sum;
    }

    function getTotalSumWithServicesWithoutVatAttribute()
    {
        return Price::removeVat($this->total_sum_with_services,
            ($this->getContractorRequisite() && $this->contractorRequisite->vat_system === Price::TYPE_CASHLESS_VAT
                ? $this->company_branch->domain->vat
                : 0));
    }

    function getCostPerUnitWithoutVatAttribute()
    {
        return Price::removeVat($this->cost_per_unit,
            ($this->getContractorRequisite() && $this->contractorRequisite->vat_system === Price::TYPE_CASHLESS_VAT
                ? $this->company_branch->domain->vat
                : 0));
    }

    function getDeliveryCostWithoutVatAttribute()
    {
        return Price::removeVat($this->delivery_cost,
            ($this->getContractorRequisite() && $this->contractorRequisite->vat_system === Price::TYPE_CASHLESS_VAT
                ? $this->company_branch->domain->vat
                : 0));
    }

    public function  isVat()
    {
        return $this->getContractorRequisite() && $this->contractorRequisite->vat_system === Price::TYPE_CASHLESS_VAT;
    }

    function getReturnDeliveryWithoutVatAttribute()
    {
        return Price::removeVat($this->return_delivery,
            ($this->getContractorRequisite() && $this->contractorRequisite->vat_system === Price::TYPE_CASHLESS_VAT
                ? $this->company_branch->domain->vat
                : 0));
    }

    function getValueAddedWithoutVatAttribute()
    {
        return Price::removeVat($this->value_added,
            ($this->getContractorRequisite() && $this->contractorRequisite->vat_system === Price::TYPE_CASHLESS_VAT
                ? $this->company_branch->domain->vat
                : 0));
    }

    function getAmountWithoutVatAttribute()
    {
        return Price::removeVat($this->amount,
            ($this->getContractorRequisite() && $this->contractorRequisite->vat_system === Price::TYPE_CASHLESS_VAT
                ? $this->company_branch->domain->vat
                : 0));
    }

    function getTotalSumWithoutVatAttribute()
    {
        return Price::removeVat($this->total_sum,
            ($this->getContractorRequisite() && $this->contractorRequisite->vat_system === Price::TYPE_CASHLESS_VAT
                ? $this->company_branch->domain->vat
                : 0));
    }

    function getPartsSumAttribute()
    {
        $sum = 0;
        $this->parts->each(function ($part) use (&$sum) {
            $sum += $part->cost_per_unit * $part->amount;
        });

        return $sum;
    }

    /**
     * @param  bool  $subContractorCalculation
     */
    public function setSubContractorCalculation(bool $subContractorCalculation): void
    {
        $this->subContractorCalculation = $subContractorCalculation;
    }

    function getDescriptionAttribute($val)
    {
        return $val ?: $this->worker->name;
    }

    function getAmountWithServicesAttribute()
    {
        return $this->amount + $this->services_sum;
    }

    public function getInvoiceDuration()
    {
        return $this->is_month ? $this->order_duration / $this->month_duration : 0;
    }
}
