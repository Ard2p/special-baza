<?php


namespace Modules\ContractorOffice\Services\Tariffs;


use App\Machinery;
use Modules\ContractorOffice\Entities\Vehicle\Price;

class ConcreteMixerCalculation
{
    function calculateCost(Machinery $vehicle, $order_type,  $distance, string $pay_type,  $options = [])
    {
        return 0;
        $price = 0;

        $options = toObject($options);
        $concrete = $options->concrete;

        $collection = collect($vehicle->waypoints_price->distances);

        $fail = true;

        foreach ($collection as $item) {

            if(!isset($previous_distance)) {
                $previous_distance = $item['distance'];
                continue;
            }
            $current_price = $item[$pay_type];
            $current_distance = $item['distance'];

            if($distance <= $current_distance && $distance >= $previous_distance) {
                $price += $current_price * $concrete;
                $fail = false;
                break;
            }
            $previous_distance = $current_distance;

        }
        if($collection->count() === 1) {

            $price += $collection->first()[$pay_type] * $concrete;

            $fail = false;

        }
        if($fail) {
            $price += $concrete * ($current_price ?? 0);
        }
        return $price;
     }
}