<?php

namespace Modules\Dispatcher\Entities;

use App\Directories\LeadRejectReason;
use App\Machines\OptionalAttribute;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use App\Overrides\Model;
use Illuminate\Support\Facades\Auth;
use Modules\CompanyOffice\Services\BelongsToCompanyBranch;
use Modules\CompanyOffice\Services\HasContacts;
use Modules\CompanyOffice\Services\HasManager;
use Modules\CompanyOffice\Services\InternalNumbering;
use Modules\ContractorOffice\Entities\Vehicle\Price;
use Modules\Dispatcher\Services\LeadService;
use Modules\Integrations\Entities\Telpehony\TelephonyCallHistory;
use Modules\RestApi\Traits\HasCoordinates;
use OwenIt\Auditing\Contracts\Auditable;

class PreLead extends Model implements Auditable
{

    use BelongsToCompanyBranch, HasManager, HasCoordinates, InternalNumbering, \OwenIt\Auditing\Auditable, HasContacts;

    protected $table = 'dispatcher_pre_leads';

    const STATUS_OPEN = 'open';
    const STATUS_ACCEPT = 'accept';
    const STATUS_REJECT = 'reject';

    protected $fillable = [
        'name',
        'contact_person',
        'status',
        'phone',
        'email',
        'address',
        'coordinates',
        'source',
        'date_from',
        'order_duration',
        'order_type',
        'comment',
        'rejected',
        'reject_type',
        'lead_id',
        'customer_id',
        'creator_id',
        'comment',
        'object_name',
        'company_branch_id',
    ];


    protected $dates = ['date_from'];

    protected $auditInclude = [
        'comment'
    ];
    //protected $with = ['positions'];

    function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    function lead()
    {
        return $this->belongsTo(Lead::class);
    }

    function rejectType()
    {
        return $this->hasOne(LeadRejectReason::class, 'key', 'reject_type');
    }

    function positions()
    {
        return $this->hasMany(PreLeadPosition::class);
    }

    function getNameAttribute($val)
    {
        return $val ?: "Запрос на аренду #{$this->internal_number} {$this->customer?->company_name} {$this->object_name}";
    }
    function transformToLead()
    {
        $service = new LeadService();

        $service->setSource($this->source);
        $leadFields = [
            'customer_name' => $this->contact_person,
            'title' => "Запрос на аренду #{$this->internal_number} {$this->customer->company_name} {$this->object_name}",
            'phone' => $this->phone,
            'email' => $this->email,
            'address' => $this->address,
            'comment' => $this->comment,
            'pay_type' => 'cashless',
            'city_id' => null,
            'region_id' => null,
            'publish_type' => Lead::PUBLISH_MAIN,
            'creator_id' => $this->creator_id,
            'company_branch_id' => $this->company_branch->id,
            'coordinates' => $this->coordinates,
        ];
        $leadPositions = [];

        foreach ($this->positions as $position) {
            $leadPositions[] = [
                'id' => $position->category_id,
                'order_type' => $position->order_type,
                'order_duration' => $position->order_duration,
                'date_from' => $position->date_from,
                'start_time' => $position->time_from,
                'count' => $this->count ?: 1,
                'machinery_model_id' => $this->model_id,

                'vehicle_id' => $this->machinery_id,
                'optional_attributes' => $position->attributes()->get()->mapWithKeys(function ($attribute) {
                    return [$attribute->id => $attribute->pivot->value];
                })

            ];
        }
        $leadFields['vehicles_categories'] = $leadPositions;

        $service
            ->setDispatcherCustomer($this->customer)
            ->createNewLead($leadFields, $this->company_branch->id, Auth::id());

        $this->update([
            'status' => self::STATUS_ACCEPT
        ]);

        $lead = $service->getLead();

        $this->update(['lead_id' => $lead->id]);

        $this->audits()->update([
            'auditable_type' => Lead::class,
            'auditable_id' => $lead->id,
        ]);

        TelephonyCallHistory::query()->where('bind_type', self::class)
            ->where('bind_id', $this->id)
            ->update([
                'bind_type' => Lead::class,
                'bind_id' => $lead->id,
            ]);

        return $lead;
    }

    function reject($type, $reason = '')
    {
        $this->update([
            'status' => self::STATUS_REJECT,
            'rejected' => $reason,
            'reject_type' => $type,
        ]);

        return $this;
    }

    function getDateToAttribute()
    {
        if($this->order_type && $this->date_from && $this->order_duration)
        {
            return getDateTo($this->date_from, $this->order_type, $this->order_duration);
        }

        return null;
    }

    function scopeForPeriod(Builder $q, Carbon $dateFrom, Carbon $dateTo)
    {
        $dateFrom->startOfDay();
        $dateTo->endOfDay();

        return $q
            ->whereNotNull('date_from')
            ->whereNotNull('order_type')
            ->selectRaw("*, (CASE 
                        WHEN order_type = 'shift'
                        THEN DATE_ADD(date_from, INTERVAL (order_duration - 1) DAY)
                         WHEN order_type ='hour'
                        THEN DATE_ADD(date_from, INTERVAL order_duration HOUR)
                        END) as date_end")
                ->havingBetween('date_end',  [$dateFrom, $dateTo])
                    ->orHavingRaw('(date_from BETWEEN ? AND ?)',  [$dateFrom, $dateTo])
                    ->orHavingRaw('(date_from <= ? AND date_end >= ?)',  [$dateFrom, $dateFrom])
                    ->orHavingRaw('(date_from <= ? AND date_end >= ?)',  [$dateTo, $dateTo]);

    }
}
