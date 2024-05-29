<?php

namespace Modules\PartsWarehouse\Services;

use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Modules\PartsWarehouse\Entities\Stock\Item;
use Modules\PartsWarehouse\Entities\Warehouse\CompanyBranchWarehousePart;
use Modules\PartsWarehouse\Entities\Warehouse\WarehousePartsOperation;

class RentService
{
    public function getRentPartsCountForPeriod(
        $from,
        $to,
        $type = null,
        int $stockId = null,
        string $name = null,
        string $vendorCode = null,
        int $brandId = null
    ) {
        $parts = Item::getPartsForRent(
            $stockId,
            $name,
            $vendorCode,
            $brandId,
            $type
        );

        $period = CarbonPeriod::create($from, $to);
        $result = [];
        foreach ($parts as $part) {
            $datesAv = [];
            $cbwp = CompanyBranchWarehousePart::query()
                ->where('company_branch_id', $part->company_branch_id)
                ->where('part_id', $part->part_id)
                ->where('is_rented', true)
                ->first();

            $period->forEach(function (Carbon $day) use ($cbwp, $part, &$datesAv) {
                $d = $day->format('Y-m-d H:i:s');
                $datesAv[] = $part->part->getSameCountForBranch() - WarehousePartsOperation::query()
                        ->where('company_branches_warehouse_part_id', $cbwp->id)
                        ->whereRaw("DATE(begin_date) <= '$d'")
                        ->whereRaw("DATE(end_date) >= '$d'")
                        ->where("type", 'rent')
                        ->get()
                        ->sum('count');
            });
            $part->available_rent_amount = min($datesAv);
            $result[] = $part;
        }
        return $result;
    }
}
