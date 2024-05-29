<?php

namespace App\Mail;

use App\Marketing\EmailLink;
use App\Marketing\ShareList;
use Fedeisas\LaravelMailCssInliner\CssInlinerPlugin;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;
use KodiCMS\Assets\Css;

class ShareToFriends extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     *
     * @return void
     */

    protected $share, $subject_message, $disUrl;

    public function __construct($subject, EmailLink $share)
    {
        $this->share = $share;

        $this->disUrl = route('article_index', ['article' => 'sorry', 'dis_share_email_id' => $share->id]);

        $this->subject_message = $subject;
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
        $this->share->pixel_url = route('check_email_friend_pixel', $this->share->id);
        return $this->usingSendersSmtp()->subject($this->subject_message)->view('email.shareByFriend')
            ->with( 'share', $this->share)
            ->with('disUrl',$this->disUrl);
    }
}
