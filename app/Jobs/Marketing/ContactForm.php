<?php

namespace App\Jobs\Marketing;

use App\Mail\ContactFormMail;
use App\Mail\SubscribeMail;
use App\Marketing\Mailing\EmailList;
use App\Marketing\Mailing\Template;
use App\Marketing\SendingMails;
use App\Marketing\SubmitContactForm;
use App\User\SendingSubscribe;
use App\User\SubscribeTemplate;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\Mail;

class ContactForm implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;


    protected $form, $email_list, $resend, $sending, $submit;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(\App\Marketing\ContactForm $form, EmailList $email, SubmitContactForm $submit = null, $resend = false, $sending = null)
    {
        $this->form = $form;
        $this->email_list = $email;
        $this->resend = $resend;
        $this->sending = $sending;
        $this->submit = $submit;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {

        $template = $this->form->template;
        if ($this->resend && !is_null($this->sending)) {
            $template = $this->sending->template;
        }

        $sending = SendingMails::create([
            'email_list_id' => $this->email_list->id,
            'confirm_status' => 0,
            'template_id' => $template->id,
            'contact_form_id' => $this->form->id,
            'hash' => str_random(8),
        ]);

        if($this->submit){
            $this->submit->update([
                'email_list_id' => $this->email_list->id,
                'sending_mail_id' => $sending->id
            ]);
        }

        Mail::to($this->email_list->email)->queue(new ContactFormMail($template->text, $template->name, $sending));

        //  $user->addNotificationHistory('notification', 'email');

    }
}
