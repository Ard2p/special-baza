<?php

namespace App\Console\Commands;

use App\Finance\TinkoffMerchantAPI;
use App\Finance\TinkoffPayment;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\Orders\Jobs\CancelPayment;

class ClearExpiredPayments extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'payments:clear_expired';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clear expired payments and unhold vehicles';

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
        $tinkoff = new TinkoffMerchantAPI();
       $payments =  TinkoffPayment::hasHold()
           ->whereNotIn('status', TinkoffMerchantAPI::FINAL_STATUSES)
           ->where('created_at', '<', now()->subMinutes(16))
           ->get();

       foreach ($payments as $t_payment){
           $status = $tinkoff->getState([
               'PaymentId' => $t_payment->interior_payment_id
           ])->status;

           if($status){
               $old_status = $t_payment->status;
               $t_payment->update(['status' => $status]);
               if(in_array($status,
                   array_merge(TinkoffMerchantAPI::BAD_STATUSES, [TinkoffMerchantAPI::DS_CHECKING]))){
                   $t_payment->payment->reverse();
               }
               if($status === TinkoffMerchantAPI::CONFIRMED
                   && $old_status !== TinkoffMerchantAPI::CONFIRMED
                   && $t_payment->hasHolds()){
                   try {
                       DB::beginTransaction();
                       $t_payment->payment->accept();

                   } catch (\Exception $exception) {
                       Log::info($exception->getMessage());
                       \DB::rollBack();
                       dispatch(new CancelPayment($t_payment))->delay(now()->addSeconds(5));
                       return response('ОК');
                   }
                   DB::commit();
               }

           }

       }

      /*  $payments =  TinkoffPayment::whereStatus('NEW')
            ->where('created_at', '<', now()->subMinutes(16))
            ->get();
        foreach ($payments as $payment){

            if($status){
                $payment->update(['status' => $status]);
            }
        }*/
    }
}
