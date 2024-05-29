<?php

namespace Modules\ContractorOffice\Filters;

use App\Overrides\ModelFilter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Arr;
use Modules\Dispatcher\Entities\Customer;
use Modules\Orders\Entities\Order;

class OrderFilter extends ModelFilter
{

    public array $equalsColumns = [
        'customer_id',
        'creator_id',
        'status',
    ];

    public array $likeColumns = [
        'internal_number',
        'address',
        'external_id',
    ];
    function today(int $val)
    {
        if ($val > 0) {
            if ($val > 1) {
                $this->active()->endTomorrow($val);
            } else {
                $this->active()->endToday();
            }
        }
    }

    function overduePays($val)
    {
        if(toBool($val)) {
            $this
                ->whereHas('components', function (Builder $q) {
                    $q->havingRaw('MAX(`date_to`) <= ?', [now()]);
                })
                ->where('status', Order::STATUS_ACCEPT)
                ->where(function($q) {
                    $q->whereHas('invoices', function (Builder $q) {
                        $q->havingRaw('SUM(paid_sum) < `orders`.`amount` AND SUM(paid_sum) > 0');
                        //$q->whereRaw('SUM(`dispatcher_invoices`.`paid_sum`) >= `amount`');
                    })->orWhereDoesntHave('invoices');
                });
        }
    }

    function startToday($val)
    {
        if(toBool($val)) {
            $this
                ->where('tmp_status', '!=', Order::STATUS_REJECT)
                ->whereHas('components', function (Builder $q) {
                $q->havingRaw('MIN(`date_from`) between ? and ?', [now()->startOfDay(), now()->endOfDay()]);
            });
        }
    }

    function startTomorrow($val)
    {
        if(toBool($val)) {
            $this->where('status', '!=', Order::STATUS_ACCEPT)->whereHas('components', function (Builder $q) {
                $q->havingRaw('MIN(`date_from`) between ? and ?', [now()->addDay()->startOfDay(), now()->addDay()->endOfDay()]);
            });
        }
    }

    function hasPledge($val)
    {
        if (toBool($val)) {
            $this->whereHas('components', function ($q) {
                $q->whereHas('services', function ($q) {
                    $q->whereHas('customService', function ($q) {
                        $q->where('is_pledge', 1);
                    });
                });

            });
        }
    }

    function rentType($val)
    {
        $val = Arr::wrap($val);
     // $rentTypesCount = [
     //     'on_the_way' => 1,
     //     'arrival' => 2,
     //     'done' => 3
     // ];
        $this->whereIn('tmp_status', $val);
        //$this->whereHas('vehicle_timestamps', fn (Builder $q) =>
        //    $q
        //        ->groupBy('machinery_id')
        //        ->havingRaw("COUNT(*) = ?", [$rentTypesCount[$val]])
        //);
    }

    function hasTasks($val)
    {
        $this->has('tasks');
    }

    function hasTasksCustomerId($val)
    {
        $this->whereHasMorph('customer', Customer::class, fn ($q) =>
            $q->has('tasks')->where('id', $val)
        );
    }

    function createdAt($val)
    {
        $this->where('created_at', 'like', "%{$val}%");
    }

    function lastIntervalDateFrom($val)
    {

    }

    function lastIntervalDateTo($val)
    {

    }

    function machineryBase($val)
    {
        $this->where('machinery_base_id', $val);
    }

    function archive($val)
    {
        return $this->whereIn('status', [Order::STATUS_CLOSE, Order::STATUS_DONE]);
    }
}
