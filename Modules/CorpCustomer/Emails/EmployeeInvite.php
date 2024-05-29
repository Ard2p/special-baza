<?php

namespace Modules\CorpCustomer\Emails;

use App\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;
use Modules\CorpCustomer\Entities\EmployeeRequest;

class EmployeeInvite extends Mailable
{
    use Queueable, SerializesModels;


    public $connect;
    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct(EmployeeRequest $connect)
    {

        $this->connect = $connect;


    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        $this->connect->load('company');
        $message = (new MailMessage())
            ->subject('Запрос от компании.')
            ->line('Компания: ' . $this->connect->company->full_name)
            ->line('Должность: ' . $this->connect->position)
            ->line('Если вы сотрудник компании, то подтвердите это.')
            ->action('Подтвердить', route('accept_employee', $this->connect->link))
            ->line('Если вы не сотрудник компании, то проигнорируйте это письмо.');

        return  $this->subject('Запрос от компании.')

            ->markdown('vendor.notifications.email', $message->data());
    }
}
