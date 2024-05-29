<?php

namespace Modules\Orders\Jobs;

use App\Finance\TinkoffPayment;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class CancelPayment implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected  $pay_system;
    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(TinkoffPayment $payment)
    {
      $this->pay_system = $payment;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $this->pay_system->payment->reverse();
    }
}
