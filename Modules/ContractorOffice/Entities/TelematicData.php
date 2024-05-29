<?php

namespace Modules\ContractorOffice\Entities;

use App\Overrides\Model;
use Modules\Integrations\Entities\WialonVehicle;

class TelematicData extends Model
{
    protected $table = 'telematic_data';


    protected $fillable = [
       'telematic_vehicle_id',
       'average_speed',
       'max_speed',
       'average_fuel',
       'begin_mileage',
       'end_mileage',
       'mileage',
       'mileage_by_work_hours',
       'working_hours',
       'time_in_motion',
       'toll_roads_mileage',
       'toll_roads_cost',
       'fuel_level_begin',
       'fuel_level_end',
       'fuel_consumption_abs',
       'fuel_consumption_fls',
       'fuel_consumption_ins',
       'fuel_drain',
       'fuel_drain_count',
       'driver',
       'telematic_type',
       'period_from',
       'period_to',
    ];


    function vehicle()
    {
       return $this->belongsTo(WialonVehicle::class, 'telematic_vehicle_id');
    }

    function scopeCurrentUser($q)
    {
        return $q->whereHas('vehicle', function ($q){
            $q->forBranch();
        });
    }
}
