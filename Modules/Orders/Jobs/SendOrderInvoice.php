<?php

namespace Modules\Orders\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\Mail;
use Modules\Orders\Entities\Order;

class SendOrderInvoice implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $order, $locale;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(Order $order)
    {
        $this->locale = \App::getLocale();
        $this->order = $order;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        \App::setLocale($this->locale);
        $this->order->refresh();

        $message = (new MailMessage())
            ->subject(trans('transbaza_order.order_invoice') . " {$this->order->id}")
            ->line(trans('transbaza_order.generate_invoice') . ' #' . $this->order->payment->invoice->number);

        /* if (!$user->hasOnlyWidgetRole()) {
             $message->line("Регион и город: {$user->region_name}, {$user->city_name}");
         }*/
        $message->action(trans('transbaza_order.download_invoice'), $this->order->payment->invoice_link);


        $this->order->company_branch->sendEmailNotification(new \App\Mail\Subscription($message, trans('transbaza_order.order_invoice') . " {$this->order->id}"));
    }
}
