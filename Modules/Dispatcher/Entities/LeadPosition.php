<?php

namespace Modules\Dispatcher\Entities;

use App\Machinery;
use App\Machines\MachineryModel;
use App\Machines\Type;
use App\Overrides\Model;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Modules\PartsWarehouse\Entities\Warehouse\WarehousePartSet;


class LeadPosition extends Model
{
//    protected $dateFormat = 'Y-m-d H:i:sO';

    protected $table = 'lead_positions';
    public $timestamps = false;

    protected $fillable = [
        'lead_id',
        'type_id',
        'order_type',
        'order_duration',
        'count',
        'date_from',
        'waypoints',
        'params',
        'machinery_model_id',
        'warehouse_part_set_id',
        'optional_attributes',
        'first_date_rent',
        'accepted',
        'is_month',
        'month_duration',
    ];


    // protected $with = ['model'];

    protected $appends = ['name', 'request_vehicles', 'rent_date_from', 'rent_time_from', 'month_order_duration'];

    protected $casts = [
        'waypoints'           => 'object',
        'params'              => 'object',
        'optional_attributes' => 'object',
        'first_date_rent' => 'date:Y-m-d',
        'is_month' => 'boolean',
        'accepted' => 'boolean',
    ];

    protected $dates = [
        'date_from'
    ];

    function lead()
    {
        return $this->belongsTo(Lead::class);
    }

    function category()
    {
        return $this->belongsTo(Type::class, 'type_id')->with('optional_Attributes');
    }

    function model()
    {
        return $this->belongsTo(MachineryModel::class, 'machinery_model_id');
    }

    function warehouse_part_set()
    {
        return $this->belongsTo(WarehousePartSet::class);
    }

    /**
     * Техника на которую делали заявку из маркетплейса
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    function vehicles()
    {
        return $this->belongsToMany(Machinery::class, 'dispatcher_lead_positions_machineries');
    }

    function getRequestVehiclesAttribute()
    {
        return $this->vehicles()->pluck('id');
    }


    function getNameAttribute()
    {
        $model =
            $this->model
                ? $this->model->name
                : '';
        $this->category->localization();
        return trim("{$this->category->name} {$model}");
    }


    function getDateToAttribute()
    {
        return getDateTo($this->date_from, $this->order_type, $this->order_duration);
    }

    function getCategoryOptionsAttribute()
    {
        if($this->optional_attributes) {
            return collect($this->optional_attributes)->map(function ($item, $id) {
                $attr = $this->category->optional_attributes->firstWhere('id', $id);
                 return $attr && $item ?  "{$attr->name} - {$item} {$attr->unit}" : null;
            })->filter(fn($item) => !!$item);
        }

        return  null;
    }

    function getRentDateFromAttribute()
    {
        return $this->date_from?->format('Y-m-d');
    }

    function getRentTimeFromAttribute()
    {
        return $this->date_from?->format('H:i');
    }

    public function getMonthOrderDurationAttribute()
    {
        return $this->is_month ? $this->order_duration / $this->month_duration : 0;
    }
}
