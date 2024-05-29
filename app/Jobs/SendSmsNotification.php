<?php

namespace App\Jobs;

use App\Service\Sms;
use App\Support\SmsNotification;
use App\User;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\App;

class SendSmsNotification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     *
     * @return void
     */

    protected $user, $sms_text, $url, $locale;

    public function __construct(User $user, $sms_text, $url)
    {
        $this->user = $user;
        $this->sms_text = $sms_text;
        $this->url = $url;
        $this->locale = App::getLocale();
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        App::setLocale($this->locale);
        if(!$this->user->phone) {
            \Log::info("No phone for user #{$this->user->id} to send sms");
            return;
        }
        $sms = SmsNotification::create([
            'message' => $this->sms_text,
            'user_id' => $this->user->id,
            'phone' => $this->user->phone,
        ]);
        $q = ($this->url) ? 'tinyurl=1' : '';

        $result = (new Sms())->send_sms($this->user->phone, $this->sms_text, 0, 0, $sms->id, 0, false, $q);

        $sms->update([
            'status' => json_encode($result)
        ]);
    }
}
