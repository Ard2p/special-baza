<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;

class NewOrder extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     *
     * @return void
     */

    protected $proposal, $machinery;

    public function __construct($proposal, $machinery)
    {
        $this->machinery = $machinery;
        $this->proposal = $proposal;
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

    /**
     * Get the mail representation of the notification.
     *
     * @param  mixed $notifiable
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    public function toMail($notifiable)
    {
        return (new MailMessage)
            ->level('error')
            ->subject('Новый заказ.')
            ->line('Создана сделка  #' . $this->proposal->id . ' на сумму ' . $this->proposal->sum / 100)
            ->action('Перейти к заказу', route('order.show', $this->proposal->id))
            ->line('Для техники #' . $this->machinery->name)
            ->action('Перейти к технике', url('/machinery/' . $this->machinery->id));
    }

    /**
     * Get the array representation of the notification.
     *
     * @param  mixed $notifiable
     * @return array
     */
    public function toArray($notifiable)
    {
        return [
            //
        ];
    }
}
