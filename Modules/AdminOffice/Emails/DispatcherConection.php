<?php

namespace Modules\AdminOffice\Emails;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;

class DispatcherConection extends Mailable
{
    use Queueable, SerializesModels;

    public  $message;
    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->message = new MailMessage();

        $this->message
            ->greeting('Поздравляем!')
            ->line('Вам подключена специализированная CRM система для владельцев спецтехники.')
            ->line('С её помощью вы сможете перевести свой бизнес в онлайн, управлять всеми заявками, не теряя клиентов, передавать лишние заказы своим подрядчикам и зарабатывать на этом')
            ->line('Во вложении описание по работе.')
            ->line('Работайте в системе бесплатно не нарушая режим самоизоляции, потому что система доступна с любого устройства. ')
            ->line('Наша миссия – помогать эффективно управлять коммерческим транспортом и зарабатывать деньги на его аренде.')
            ->line('С уважением, Команда TRANSBAZA.')
            ->line('info@transbaza.ru +7 (495)975-75-28');


    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->subject('Подключение специализированной CRM')
            //    ->usingSendersSmtp()
            ->markdown('vendor.notifications.email', $this->message->data())
            ->attach(public_path('documents/crm_description.pdf'));
    }
}
