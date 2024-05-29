<?php

namespace App\Jobs\Mailing;

use App\Mail\EmailMarketing;
use App\Marketing\Mailing\MailingList;
use App\Marketing\Mailing\Template;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class Email implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $emails, $template, $mailing;
    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($emails, Template $template, MailingList $mailing)
    {
        $this->emails = $emails;
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
        foreach ($this->emails as $email) {
            Mail::to($email)->queue(new EmailMarketing($this->template->text, $this->mailing->subject));
        }

        $this->mailing->update([
            'status' => 1
        ]);
    }

    public function fail($exception = null)
    {
        Log::info(json_encode($exception));
    }
}
