<?php

namespace App\Mail;

use App\Marketing\ShareList;
use Fedeisas\LaravelMailCssInliner\CssInlinerPlugin;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;
use KodiCMS\Assets\Css;

class EmailMarketing extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     *
     * @return void
     */

    protected $template;

    public function __construct($template, $subject)
    {

        $this->template = $template;
        $this->subject($subject);
        $this->from(env('MAIL_SUBSCRIBE_FROM_ADDRESS'), env('MAIL_SUBSCRIBE_FROM_NAME'));
    }

    private function usingSendersSmtp()
    {
        $mailTransport = app()->make('mailer')
            ->getSwiftMailer()
            ->getTransport();

        if ($mailTransport instanceof \Swift_SmtpTransport) {
            /** @var \Swift_SmtpTransport $mailTransport */
            $mailTransport->setUsername(env('MAIL_SUBSCRIBE_USERNAME'));
            $mailTransport->setPassword(env('MAIL_SUBSCRIBE_PASSWORD'));
            // Port and authentication can also be configured... You get the picture
        }

        return $this;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {

        return $this->usingSendersSmtp()->view('email.marketing')
            ->with('template', $this->template);
    }
}
