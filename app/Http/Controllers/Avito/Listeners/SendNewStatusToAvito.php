<?php

namespace App\Http\Controllers\Avito\Listeners;

use App\Http\Controllers\Avito\Events\OrderChangedEvent;
use App\Http\Controllers\Avito\Models\AvitoOrder;
use App\Service\Avito\AvitoApiService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Queue;
use Laravel\Horizon\Events\JobFailed as HorizonJobFailed;
use Mail;
use Throwable;

class SendNewStatusToAvito implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;


    public $tries = 4;
    public $maxExceptions = 5;
    public $backoff = [60, 120, 240, 300];

//    private $avitoOrder;
    public function __construct()
    {

    }

    public function tags()
    {
        return ['avito:send-new-status'];
    }

    public function handle(OrderChangedEvent $event)
    {
        if ($event->oldStatus === $event->newStatus && $event->newStatus !== 4) {
            return;
        }

        $mailEnabled = config('services.mail.enabled');

        $avitoOrder = AvitoOrder::query()->where('order_id', $event->order->id)
            ->latest()
            ->first();
        $notifyMails = config('avito.notify_mails');
        if ($avitoOrder) {
            $ssl = config('app.ssl');
            $frontUrl = config('app.front_url');
            $companyBranchId = $event->order->company_branch->id;
            $companyAlias = $event->order->company_branch->company->alias;

            $url = "$ssl://$companyAlias.$frontUrl/branch/$companyBranchId/orders/{$event->order->id}";

            if (($event->newStatus === AvitoOrder::STATUS_CANCELED && $event->oldStatus > 2) || $event->newStatus !== AvitoOrder::STATUS_CANCELED) {
                $avitoApi = new AvitoApiService();
                $avitoApi->sendOrderStatusChanged($avitoOrder, $event->oldStatus, $event->newStatus);

                if ($mailEnabled) {
                    Mail::send([], [], function ($message) use ($event, $avitoOrder, $url, $notifyMails) {
                        $message->to($notifyMails)
                            ->subject("Status changed for TB Order Id: {$event->order->id}, Avito Order Id: $avitoOrder->avito_order_id")
                            ->setBody("<p>Status changed from: $event->oldStatus to $event->newStatus</p><p>OrderURL: $url</p>", 'text/html'); // for HTML rich messages
                    });
                }


            } else {
                if ($mailEnabled) {
                    Mail::send([], [], function ($message) use ($event, $avitoOrder, $url, $notifyMails) {
                        $message->to($notifyMails)
                            ->subject("Событие – ручная отмена сделки Авито #$avitoOrder->avito_order_id")
                            ->setBody("<p>Отменена сделка Авито #$avitoOrder->avito_order_id.</p><p>Статус сделки: {$avitoOrder->order->tmp_status}.</p><p>Статус Авито: $avitoOrder->status</p><p>Для просмотра перейдите по ссылке: <a href='$url'>$url</a></p>", 'text/html'); // for HTML rich messages
                    });
                }
            }

            return;
        }

        throw new \Exception("Заказ с номером $avitoOrder->avito_order_id в системе не найден");
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
