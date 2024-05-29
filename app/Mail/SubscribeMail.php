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

class SubscribeMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     *
     * @return void
     */

    protected $template, $sending;

    public function __construct($template, $subject, $sending)
    {

        $this->template = $template;
        $this->sending = $sending;
        $this->subject('TRANSBAZA - ' . $subject);
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

        $unsubscribe = route('article_index', ['article' => 'unsubscribe', 'un_subscribe_sending_id' => $this->sending->id, 'hash' =>$this->sending->hash]);
        $confirm_link =  route('article_index', ['article' => 'thankyou', 'subscribe_sending_id' => $this->sending->id, 'hash' =>$this->sending->hash]);
        $cancel_link = route('article_index', ['article' => 'verysorry', 'dis_subscribe_sending_id' => $this->sending->id, 'hash' =>$this->sending->hash]);
        return $this->view('email.subscribe')
            ->usingSendersSmtp()
            ->with('template', $this->template->html)
            ->with('unsubscribe', $unsubscribe)
            ->with('confirm_link', $confirm_link)
            ->with('system', $this->template->subscribe->can_unsubscribe === 0)
            ->with('pixel_url', route('check_subscribe_pixel', $this->sending->id))
            ->with('cancel_link', $cancel_link);
    }
}
