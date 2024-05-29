<?php

namespace Modules\Orders\Entities;

use App\Machinery;
use App\Overrides\Model;

class MachineryStamp extends Model
{

    protected $table = 'machinery_stamps';


    const STATUS_PREPARE = 'on_the_way';
    const STATUS_START = 'arrival';
    const STATUS_FINISH = 'done';
    protected $fillable = [
        'order_id',
        'machinery_id',
        'machinery_type',
        'current_datetime',
        'type',
        'coordinates'
    ];

    protected $dates = ['current_datetime'];

    function setCoordinatesAttribute($val)
    {
        if ($val) {
            $coords = explode(',', trim($val));
            $query = "GeomFromText('POINT($coords[0] $coords[1])')";
            $this->attributes['coordinates'] = \DB::raw($query);
        }
    }

    function getCoordinatesAttribute($value)
    {
        return getDbCoordinates($value);
    }


    static function createTimestamp($machine_id, $order_id, $type, $dateTime, $coordinates = null, $classType = Machinery::class)
    {
        $fields = [
            'machinery_id' => $machine_id,
            'machinery_type' => $classType,
            'order_id' => $order_id,
            'type' => $type,
            'current_datetime' => $dateTime,
            'coordinates' => $coordinates,
        ];
        self::query()->where('machinery_id', $machine_id)
            ->where('order_id', $order_id)
            ->whereNull('type')
            ->where('machinery_type', $classType)->delete();

        $stamp = self::query()->where('machinery_id', $machine_id)
            ->where('order_id', $order_id)
            ->where('type', $type)
            ->where('machinery_type', $classType)
            ->first();
        if ($stamp) {
            $stamp->update($fields);
        } else {
            $stamp = MachineryStamp::create($fields);
        }
        $tmpStatus = null;
        switch ($type) {
            case 'on_the_way':
                $tmpStatus = Order::STATUS_PREPARE;
                break;
            case 'arrival':
                $tmpStatus = Order::STATUS_START;
                break;
            case 'done':
                $tmpStatus = Order::STATUS_FINISH;
                break;
        }

        $order = Order::query()->where('id', $order_id)->first();
        $update = true;

        foreach ($order->components as $component){
            $stampType = self::query()->where('machinery_id', $component->worker->id)
                ->where('order_id', $order_id)
                ->where('machinery_type', $classType)
                ->orderByDesc('current_datetime')?->first()?->type;
            if($stampType != $type){
                $update = false;
            }
        }
        if($update) {
            $order->update([
                'tmp_status' => $tmpStatus
            ]);
        }

        return $stamp;
    }

    function order()
    {
        return $this->belongsTo(Order::class);
    }
}
