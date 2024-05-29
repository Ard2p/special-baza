<?php

namespace App\Notifications;

use App\Directories\TransactionType;
use App\User\BalanceHistory;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Queue\InteractsWithQueue;

class BalanceNotification extends Notification implements ShouldQueue
{
    use Queueable, InteractsWithQueue;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public $tries = 3;

    protected $tr_model;

    public function __construct($model)
    {
        $this->tr_model = $model;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param  mixed $notifiable
     * @return array
     */
    public function via($notifiable)
    {
        return ['mail'];
    }

    public function toArray($notifiable)
    {

        return [
            $this->tr_model
        ];
    }

    /**
     * Get the mail representation of the notification.
     *
     * @param  mixed $notifiable
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    public function toMail($notifiable)
    {
        //dd($notifiable);
        $reason = BalanceHistory::TYPES_LNG[$this->tr_model->type];
        $url = '/' . $this->tr_model->billing_type . '/balance';
        $role = ($this->tr_model->billing_type == 'customer') ? 'Заказчик' : 'Исполнитель';
        return (new MailMessage)
            ->subject('Изменение баланса.')
            ->line('У Вас был изменен баланс для роли: ' . $role)
            ->line('Сумма: ' . $this->tr_model->sum / 100)
            ->line('Акутальный баланс: ' . $this->tr_model->user->getBalance($this->tr_model->billing_type) / 100 )
            ->line('Причина изменения: ' . $reason)
            ->action('История баланса', url($url));
    }

}
