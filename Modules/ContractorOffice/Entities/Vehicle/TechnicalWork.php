<?php

namespace Modules\ContractorOffice\Entities\Vehicle;

use App\Machinery;
use App\Machines\BusyType;
use App\Machines\FreeDay;
use Illuminate\Database\Eloquent\Model;
use Modules\ContractorOffice\Entities\CompanyWorker;
use Modules\Orders\Entities\OrderComponent;
use Modules\Orders\Entities\Service\ServiceCenter;

class TechnicalWork extends Model
{

    protected $table = 'machinery_technical_works';
    protected $fillable = [
        'engine_hours',
        'type',
        'description',
        'service_center_id',
        'order_component_id',
        'report_data',
        'machinery_id'
    ];

    protected $appends = ['date_from', 'date_to'];

    const TYPE_REPAIR = 'repair';
    const TYPE_SERVICE = 'service';
    const TYPE_APPRAISE = 'appraise';

    protected $casts = [
        'report_data' => 'object'
    ];

    function orderComponent()
    {
        return $this->belongsTo(OrderComponent::class, 'order_component_id');
    }

    function serviceCenter()
    {
        return $this->belongsTo(ServiceCenter::class);
    }

    function busyType()
    {
        return $this->hasOne(BusyType::class, 'key', 'type');
    }

    function machine()
    {
        return $this->belongsTo(Machinery::class);
    }

    function periods()
    {
        return $this->hasMany(FreeDay::class);
    }

    function mechanics()
    {
        return $this->belongsToMany(CompanyWorker::class, 'technical_works_mechanics');
    }

    function getDateFromAttribute()
    {
        return $this->periods->first()
            ?  (string) $this->periods->first()->startDate
            : null;
    }

    function getDateToAttribute()
    {
        return $this->periods->first()
            ? (string) $this->periods->last()->endDate
            : null;
    }
}
