<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;
use Modules\ContractorOffice\Services\Tariffs\TimeCalculation;
use Modules\Orders\Entities\Order;
use Modules\Orders\Services\OrderService;
use Modules\PartsWarehouse\Entities\Warehouse\WarehousePartSet;

class UpdateOrderActualValues extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'orders:update-overdue';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $orders = Order::query()->with('components.actual')
            ->whereDoesntHave('machinerySets')
            ->whereHas('company_branch', fn(Builder $branchBuilder) => $branchBuilder->where('auto_prolongation', 1))
            ->whereHas('components',
            fn(Builder $q) => $q->whereBetween('date_to', [now()->subMonths(), now()->subHour()])
                ->where('status', Order::STATUS_ACCEPT)
                ->where(fn(Builder $builder) =>
                $builder->whereHas('actual', fn (Builder $componentBuilder) => $componentBuilder->where('auto', 1))
                    ->orWhereDoesntHave('actual'))
        );

        $service = new OrderService();

        /** @var Order $order */
        foreach ($orders->lazyById(50) as $order) {
            $service->setOrder($order);
            \DB::beginTransaction();
            try {
                foreach ($order->components as $component) {
                    if($component->worker instanceof WarehousePartSet || $component->date_to->gt(now(
                        $order->company_branch->timezone
                        ))){
                        continue;
                    }

                    $component->actual?->delete();

                    $diff = $component->order_type === TimeCalculation::TIME_TYPE_HOUR
                        ? round(now()->diffInMinutes($component->date_to) / 60, 2)
                        : (now($order->company_branch->timezone)->diffInDays($component->date_to));

                    if($diff === 1 && $component->order_type === TimeCalculation::TIME_TYPE_SHIFT) {
                        $diff +=1;
                    }
                    if(!$diff) {
                        $diff = 1;
                    }
                    $service->setActualApplicationData($component->id, [
                        'auto'            => 1,
                        'order_type'      => $component->order_type,
                        'order_duration'  => $component->order_duration + $diff,
                        'cost_per_unit'   => ($service->isVatSystem ? $component->cost_per_unit_without_vat : $component->cost_per_unit) / 100,
                        'value_added'     => ($service->isVatSystem ? $component->value_added_without_vat : $component->value_added) / 100,
                        'delivery_cost'   => ($service->isVatSystem ? $component->delivery_cost_without_vat : $component->delivery_cost)/ 100,
                        'return_delivery' => ($service->isVatSystem ? $component->return_delivery_without_vat : $component->return_delivery) / 100,
                        'amount'          => $component->amount / 100,
                        'hours'           => $component->order_type === TimeCalculation::TIME_TYPE_HOUR
                            ? (array_map(fn($value) => [
                                'time_from' => $value['time_from'],
                                'time_to'   => Carbon::parse($value['time_to'])->addHours($diff)->format('H:i'),
                                'date'      => $value['date'],
                                'hours'     => $component->order_duration + $diff,
                            ], $service->getApplicationWorkHours($component->id)))
                            : []

                    ]);

                }
            }catch (\Exception $exception) {
                \DB::rollBack();
                continue;
            }
            \DB::commit();
        }

        return 0;
    }
}
