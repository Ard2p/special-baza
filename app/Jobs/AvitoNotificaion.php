<?php

namespace App\Jobs;

use App\Mail\AvitoNotificationEmail;
use App\Service\Sms;
use App\Support\SmsNotification;
use App\User;
use AshAllenDesign\ShortURL\Classes\Builder;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\App;
use Modules\Orders\Entities\Order;

class AvitoNotificaion implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;


    protected Order $order;
    protected string $message;
    protected string $sms;
    protected bool $sms_send;
    protected string $shortURL;
    protected int $returnSum;

    public function tags(){
        return ['avito:send-notification'];
    }
    public function __construct($order, $message = null, int $returnSum = 0, $subject = null, $sms_send = true)
    {
        $this->onQueue('avito');
        $this->order = $order;
        $this->sms_send = $sms_send;
        $ssl = config('app.ssl');
        $frontUrl = config('app.front_url');
        $companyBranchId = $order->company_branch->id;
        $companyAlias = $order->company_branch->company->alias;

        $builder = new Builder();
        $shortURLObject = $builder->destinationUrl("$ssl://$companyAlias.$frontUrl/branch/$companyBranchId/orders/{$this->order->id}")->make();
        $this->shortURL = $shortURLObject->default_short_url;
        $date = Carbon::parse($this->order->date_from)->format("d.m.Y H:i");
        if (empty($message)) {
            $this->message = 'Событие - создание сделки с источником Avito:';
            $this->sms = <<<TEXT
Заказ с Авито #$order->external_id на $date.
Адрес: $this->order->address 
Техника: {$this->order->components->first()?->worker->name} 
См. детали. $this->shortURL
TEXT;
        } else {
            $this->message = $message;
            $this->sms = $message;
        }
        $this->returnSum = $returnSum;
        if ($this->returnSum > 0) {
            $retSum = $this->returnSum / 100;
            $this->message .= " Сумма возврата: $retSum р.";
        }
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        if ($this->order->isAvitoOrder()) {
            foreach ($this->order->company_branch->employees as $user) {
                if ($user->sms_notify && config('services.sms.enabled') && $this->sms_send) {
                    $sms = $this->sms;
                    $user->sendSmsNotification($sms);
                }

                if ($user->sms_notify && config('services.mail.enabled')) {
                    $notifyMails = config('avito.notify_mails');
                    \Mail::to($user->email)
                        ->bcc($notifyMails)
                        ->send(new AvitoNotificationEmail(
                                $subject ?? "Заказ с Авито #" . $this->order->external_id,
                                "$this->message <br><br>Для просмотра заказа перейдите по ссылке: <a href='$this->shortURL'>$this->shortURL</a><br>",
                                $this->order->company_branch->support_link
                            )
                        );
                }
            }
        }
    }
}
