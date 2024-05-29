<?php


namespace Modules\ContractorOffice\Services\Tariffs;


use App\Machinery;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Modules\ContractorOffice\Entities\System\TariffGrid;
use Modules\ContractorOffice\Entities\System\TariffGridPrice;
use Modules\ContractorOffice\Entities\Vehicle\Price;

class TimeCalculation
{
    const TIME_TYPE_SHIFT = 'shift';
    const TIME_TYPE_HOUR = 'hour';

    function calculateCost(Machinery $vehicle, $order_type, $duration, $payType = Price::TYPE_CASHLESS_WITHOUT_VAT, $driver = TariffGrid::WITHOUT_DRIVER)
    {

        /** @var TariffGrid $grid */
        $grid = $vehicle->prices()
            ->whereHas('unitCompare', function (Builder $q) use ($order_type) {
                $q->where('tariff_unit_compares.type', $order_type);
            })
            ->withCount(['unitCompare as unit_amount' => function ($query) use ($order_type) {

                $query->select(DB::raw("tariff_unit_compares.amount * tariff_grids.min"))
                    ->where('tariff_unit_compares.type', $order_type);
            }])
            ->where('type', $driver)
           /* ->where(function (Builder $q) use ($duration) {

                $q->where(function (Builder $q) use ($duration) {
                    $q->where('is_fixed', false)
                        ->having('unit_amount', '<=', $duration);
                });
                $q->orWhere(function (Builder $q) use ($duration) {
                    $q->where('is_fixed', true)
                        ->having('unit_amount', '=', $duration);
                });

            })*/
            ->havingRaw("
            unit_amount <= {$duration} "
//               CASE
//                WHEN is_fixed = true
//               THEN unit_amount <= {$duration}
//               WHEN is_fixed = false
//               THEN unit_amount = {$duration}
//               END
            )
            ->orderBy('unit_amount',  'desc')
           ->first();
      // logger($grid->toSql());
      // $grid = $grid->first();
      // logger($grid);
        if (!$grid) {
            return ['price' => 0, 'value_added' => 0];
        }
        /** @var TariffGridPrice $price */
        $price = $grid->gridPrices->where('price_type', $payType)->first();
        $currentPrice = $price ?/* $grid->unitCompare->amount * $grid->min **/
            $price->price : 0;
        //logger($currentPrice);
        $currentPrice = $currentPrice / $grid->unitCompare->amount;
        $valueAdded = $grid->gridPrices->where('price_type', 'value_added_' . $payType)->first()?->price ?: 0;
        if($vehicle->company_branch->getSettings()->price_without_vat && $payType === Price::TYPE_CASHLESS_VAT) {

            $currentPrice = Price::removeVat($currentPrice, $vehicle->company_branch->domain->country->vat);
            $valueAdded = Price::removeVat($valueAdded, $vehicle->company_branch->domain->country->vat);
        }
        if(!$vehicle->subOwner) {
            $valueAdded = 0;
        }
        return ['price' => $currentPrice, 'value_added' => $valueAdded];
    }

}
