<?php

namespace App\Notifications;

use App\Helpers\RequestHelper;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Queue\InteractsWithQueue;

class EmailConfirm extends Notification implements ShouldQueue
{
    use Queueable, InteractsWithQueue;

    /**
     * Create a new notification instance.
     *
     * @return void
     */

    public $tries = 3;

    protected $user, $domain;

    public function __construct($user)
    {
        $this->user = $user;
        $this->domain =RequestHelper::requestDomain();
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function via($notifiable)
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    public function toMail($notifiable)
    {
        return (new MailMessage)
                     ->subject('TRANS-BAZA.RU - Регистрация - Подтвердите Email')
                    ->line('Перейдите по ссылке для подтверждения email.')
                    ->action('Подтвердить email', origin("/confirm/{$this->user->confirm->token}", [], $this->domain));

    }

    /**
     * Get the array representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function toArray($notifiable)
    {
        return [
            //
        ];
    }
}
