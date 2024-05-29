<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Modules\Orders\Entities\Order;

class AvitoNotificationEmail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct(private string $subjectMessage, private string $textMessage, private $link = '')
    {
        //
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        $this->textMessage = nl2br($this->textMessage);
        return $this->subject($this->subjectMessage)
            ->view('email.avito-notification-email')
            ->with([
                'textMessage' => $this->textMessage,
                'link' => $this->link
            ]);
    }
}
