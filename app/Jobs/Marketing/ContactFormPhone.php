<?php

namespace App\Jobs\Marketing;

use App\Mail\ContactFormMail;
use App\Mail\SubscribeMail;
use App\Marketing\Mailing\EmailList;
use App\Marketing\Mailing\PhoneList;
use App\Marketing\Mailing\Template;
use App\Marketing\SendingMails;
use App\Marketing\SendingSms;
use App\Marketing\SubmitContactForm;
use App\Service\Sms;
use App\User\SendingSubscribe;
use App\User\SubscribeTemplate;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\Mail;

class ContactFormPhone implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;


    protected $form, $phone_list, $resend, $sending, $submit;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(\App\Marketing\ContactForm $form, PhoneList $phone, SubmitContactForm $submit = null, $resend = false, $sending = null)
    {
        $this->form = $form;
        $this->phone_list = $phone;
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


        $template = $this->form->phone_template;
        if ($this->resend && !is_null($this->sending)) {
            $template = $this->sending->template;
        }
        $sending = SendingSms::create([
            'phone_list_id' => $this->phone_list->id,
            'confirm_status' => 0,
            'template_id' => $template->id,
            'contact_form_id' => $this->form->id,
            'hash' => str_random(8),
        ]);
        if($this->submit){
            $this->submit->update([
                'phone_list_id' => $this->phone_list->id,
                'sending_sms_id' => $sending->id
            ]);
        }
        $link = route('get_fsk', ['sending_id' => $sending->id, 'hash' => $sending->hash]);
        $text = "Посмотри: {$link}";
        $q = 'tinyurl=1';
        $result = (new Sms())->send_sms($sending->phone, $text, 0, 0, 0, 0, false, $q);

        if ($result[1] > 0) {
            $sending->update([
                'is_watch' => 1,
            ]);
        }

    }
}
