<?php

namespace App\Notifications;

use App\Helpers\RequestHelper;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;

class ResetPassword extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    protected $token, $domain;

    public function __construct($token)
    {
       $this->token = $token;
       $this->domain = RequestHelper::requestDomain();
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
            ->subject('Восстановление пароля')
            ->line('Вы получили этот емейл так как кто-то, возможно, Вы, направил запрос на восстановление пароля.')
            ->action('Восстановить пароль', origin('password-reset', ['token' => $this->token], $this->domain))
            ->line('Если Вы не направляли запрос на восстановление пароля, просто игнорируйте данное письмо.');
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
