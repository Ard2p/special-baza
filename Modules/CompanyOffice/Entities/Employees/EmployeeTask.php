<?php

namespace Modules\CompanyOffice\Entities\Employees;

use App\Machinery;
use App\Machines\FreeDay;
use App\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use App\Overrides\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\CompanyOffice\Entities\Company\GoogleEvent;
use Modules\CompanyOffice\Services\BelongsToCompanyBranch;
use Modules\CompanyOffice\Services\HasManager;
use Modules\Dispatcher\Entities\Customer;
use Modules\Dispatcher\Entities\Lead;
use Modules\Orders\Entities\Order;

class EmployeeTask extends Model
{
    use BelongsToCompanyBranch, HasManager;

    const TYPE_EMAIL = 'email';
    const TYPE_CALL = 'call';
    const TYPE_MEETING = 'meeting';

    const STATUS_NEW = 1;
    const STATUS_IN_PROGRESS = 2;
    const STATUS_PROCESSED = 3;
    const STATUS_CANCELED = 4;

    protected $fillable = [
        'title',
        'description',
        'status',
        'important',
        'date_from',
        'date_to',
        'duration_type',
        'duration',
        'task_type',
        'creator_id',
        'employee_id',
        'company_branch_id',
        'updated_by_id',
        'responsible_id',

    ];

    protected $casts = [
        'important' => 'boolean',
    ];

    protected $dates = [
        'date_from',
        'date_to',
    ];

    public function getStatusNameAttribute()
    {
        switch ($this->status) {
            case self::STATUS_NEW:
                return 'Новая';
            case self::STATUS_IN_PROGRESS:
                return 'В работе';
            case self::STATUS_PROCESSED:
                return 'Завершена';
            case self::STATUS_CANCELED:
                return 'Отменена';
            default:
                return '';
        }
    }

    function scopeForPeriod(Builder $q, Carbon $dateFrom, Carbon $dateTo)
    {
        $dateFrom->startOfDay();
        $dateTo->endOfDay();
        return $q->where(function (Builder $q) use ($dateFrom, $dateTo) {
            $q->whereBetween('date_from', [$dateFrom, $dateTo])
            ->orWhereBetween('date_to', [$dateFrom, $dateTo])
            ->orWhere(function(Builder $q) use ($dateFrom, $dateTo) {
                $q
                    ->where('date_from', '<=', $dateFrom)
                    ->where('date_from', '<=', $dateTo)
                    ->where('date_to', '>=', $dateTo)
                    ->where('date_to', '>=', $dateTo);
            });
        });
    }

    public function google_event()
    {
        return $this->morphOne(GoogleEvent::class, 'eventable');
    }

    function employee()
    {
        return $this->belongsTo(User::class, 'employee_id');
    }

    function updated_by()
    {
        return $this->belongsTo(User::class);
    }

    function responsible()
    {
        return $this->belongsTo(User::class);
    }

    function operations()
    {
        return $this->hasMany(EmployeeTaskOperation::class);
    }

    function orders()
    {
        return $this->morphedByMany(Order::class, 'bind', 'employee_tasks_binds');
    }

    function leads()
    {
        return $this->morphedByMany(Lead::class, 'bind', 'employee_tasks_binds');
    }

    function vehicles()
    {
        return $this->morphedByMany(Machinery::class, 'bind', 'employee_tasks_binds');
    }

    function vehicleCalendar()
    {
        return $this->hasMany(FreeDay::class);
    }

    function customers()
    {
        return $this->morphedByMany(Customer::class, 'bind', 'employee_tasks_binds');
    }

    function detachAllBinds()
    {
        DB::table('employee_tasks_binds')->where('employee_task_id', '=', $this->id)->delete();
        $this->vehicleCalendar()->delete();
        $this->refresh();
        return $this;
    }

    function pushVehicleEvent($id)
    {
        $machine = Machinery::forBranch()->checkAvailable($this->date_from, $this->date_to)->find($id);
        if(!$machine) {
            $error =  ValidationException::withMessages([
                'date_from' => ['Техника занята в этот период.']
            ]);

            throw $error;
        }
        FreeDay::create([
            'startDate' => $this->date_from,
            'endDate' => $this->date_to,
            'type' => 'busy',
            'employee_task_id' => $this->id,
            'machine_id' => $machine->id,
            'creator_id' => \Auth::id(),
            'comment' => $this->title
        ]);
    }


    function getDurationAttribute($val)
    {
        if($val) {
            return Carbon::parse($val)->format('H:i');
        }
        return '';
    }
}
