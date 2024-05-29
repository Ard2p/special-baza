<?php


namespace Modules\ContractorOffice\Services\Tariffs;


use App\Machinery;
use Modules\ContractorOffice\Entities\Vehicle\Price;

class DistanceCalculation
{
    function calculateCost(Machinery $vehicle, $order_type,  $distance, string $pay_type = Price::TYPE_CASHLESS_WITHOUT_VAT)
    {
        $price = 0;
        $collection = collect($vehicle->waypoints_price->distances);

        $calc_distance = $distance;

        foreach ($collection as $item) {

            if(!isset($current_price)) {
                $current_price = $item[$pay_type];
                continue;
            }
            $calc_distance -= $item['distance'];


            if($calc_distance < 0) {
               $price += $current_price * ($item['distance'] + $calc_distance);
               break;
            }else {
                $price += $item['distance'] * $current_price;

            }

            $current_price = $item[$pay_type];
        }
        if($calc_distance > 0) {
            $price += $calc_distance * ($current_price ?? $collection->first()[$pay_type]);
        }
        return $price;
     }
}