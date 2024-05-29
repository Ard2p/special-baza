<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Modules\ContractorOffice\Entities\Vehicle\MachineryBase;
use Modules\Orders\Entities\Order;
use Modules\Orders\Services\OrderService;

class CheckOrdersPledges extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'trans:check-orders-pledges';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check orders pledges';

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
     * @return mixed
     */
    public function handle()
    {
        $orders = Order::query()->whereIn('status', [
            Order::STATUS_OPEN,
            Order::STATUS_ACCEPT
        ])->where('date_from', '<=', Carbon::now()->format('Y-m-d'))->get();

        $service = new OrderService();
        foreach ($orders as $order) {
            $base = $order->machinery_base;
            if (!$base || !$this->checkBaseSettings($base)) {
                continue;
            }

            $dateFrom = Carbon::parse($order->date_from);

            $sumIn = $order->pays()->where([
                'invoice_pays.type' => 'in'
            ])->sum('invoice_pays.sum');

            $sumOut = $order->pays()->where([
                'invoice_pays.type' => 'out'
            ])->sum('invoice_pays.sum');

            $sumPayed = $sumIn - $sumOut;
            if (!empty($base->cancel_after) &&
                $dateFrom->diffInHours(Carbon::now(), false) >= $base->cancel_after
                && $sumPayed < $order->amount * ($base->payment_percent / 100)
            ) {
                DB::beginTransaction();
                foreach ($order->workers->where('status', Order::STATUS_ACCEPT) as $worker) {
                    $service->setOrder($order)->rejectApplication($worker->id, 'no_payment_transferred');
                }
                DB::commit();
            }
        }
    }

    private function checkBaseSettings(MachineryBase $base)
    {
        if (empty($base->cancel_after) || empty($base->payment_percent)) {
            return false;
        }
        return true;
    }
}
