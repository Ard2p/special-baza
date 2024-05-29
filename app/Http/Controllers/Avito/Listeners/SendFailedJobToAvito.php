<?php

namespace App\Http\Controllers\Avito\Listeners;

use App\Http\Controllers\Avito\Events\OrderChangedEvent;
use App\Http\Controllers\Avito\Events\OrderFailedEvent;
use App\Service\Avito\AvitoApiService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Mail;

class SendFailedJobToAvito implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    public function __construct()
    {
        $this->onQueue('avito');
    }

    public $tries = 4;
    public $maxExceptions = 5;
    public $backoff = [60, 120, 240, 300];

    public function handle(OrderFailedEvent $event)
    {
        $avitoApi = new AvitoApiService();
        $avitoApi->sendOrderStatusChanged($event->avitoOrder, $event->oldStatus, $event->newStatus);

        $mailEnabled = config('services.mail.enabled');
        if ($mailEnabled) {
            $notifyMails = config('avito.notify_mails');
            Mail::send([], [], function ($message) use ($event, $notifyMails) {
                $message->to($notifyMails)
                    ->subject("Order canceled")
                    ->setBody("<h1>Avito order canceled. Id: {$event->avitoOrder?->id} avitoOrderId {$event->avitoOrder?->avito_order_id}</h1><br><p>Reason: $event->message</p>", 'text/html'); // for HTML rich messages
            });
        }
    }

    public function failed(OrderChangedEvent $event)
    {
        logger()->error('Queue failed!', [$event]);
        \Illuminate\Support\Facades\Mail::send([], [], function ($message) use ($event) {

            $ssl = config('app.ssl');
            $frontUrl = config('app.front_url');
            $companyBranchId = $event->order->company_branch->id;
            $companyAlias = $event->order->company_branch->company->alias;

            $url = "$ssl://$companyAlias.$frontUrl/branch/$companyBranchId/orders/{$event->order->id}";
            $notifyMails = config('avito.notify_mails');
            $message->to($notifyMails)
                ->subject("Ошибка отправки статуса в Авито! Avito Order Id: {$event->order->external_id}")
                ->setBody("<p>Попытка отправки статса: {$event->oldStatus} -- {$event->newStatus}</p><p>Для просмотра перейдите по ссылке: <a href='$url'>$url</a></p>", 'text/html'); // for HTML rich messages
        });
    }
}
