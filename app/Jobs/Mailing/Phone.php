<?php

namespace App\Jobs\Mailing;

use App\Mail\EmailMarketing;
use App\Marketing\Mailing\MailingList;
use App\Marketing\Mailing\Template;
use App\Service\Sms;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\Mail;

class Phone implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $phones, $template, $mailing;
    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($phones, Template $template, MailingList $mailing)
    {
        $this->phones = $phones;
        $this->mailing = $mailing;
        $this->template = $template;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        foreach ($this->phones as $phone) {
            $q = 'tinyurl=1';
             (new Sms())->send_sms($phone, $this->template->text, 0, 0, 0, 0, false, $q);

        }

        $this->mailing->update([
            'status' => 1
        ]);
    }
}
