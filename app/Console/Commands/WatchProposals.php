<?php

namespace App\Console\Commands;


use App\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Modules\Dispatcher\Entities\Lead;
use Modules\Orders\Entities\Order;

class WatchProposals extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'proposals:close';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Close expired proposals';

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
        /*   $proposals = Proposal::checkAvailable()->where('date', '<', now()->subHour(2))->get();
           $proposals->each(function ($proposal){
                   $proposal->update([
                       'status' => Proposal::status('close')
                   ]);
           });*/

        $orders = Order::query()->where('status', Order::STATUS_ACCEPT)
            ->whereHas('components', function ($q) {
                 $q->where('date_to', '<', now()->subDay());
                 })
            ->whereDoesntHave('components', function ($q) {
                $q->where('date_to', '>', now()->subDay());
            })
            ->get();

/*        foreach ($orders as $order) {
            DB::beginTransaction();

              $order->done();

              DB::commit();
        }*/

        $orders = Order::query()->where('status', Order::STATUS_HOLD)
            ->whereDate('date_from', '<', now()->addHours(8))
            ->get();

        DB::beginTransaction();

        $orders->each(function ($order){
            $order->payment->reverse();
        });

        DB::commit();

        $leads = Lead::query()
            //->whereDoesntHave('orders')
            ->whereStatus(Lead::STATUS_OPEN)
            //->where('customer_type', User::class)
            ->whereDate('start_date', '<', now())
        ->get();

        foreach ($leads as $lead) {
            if(now()->gt($lead->date_to) && now()->diffInDays($lead->date_to) > 1) {
                $lead->setExpired();
            }

        }
    }
}
