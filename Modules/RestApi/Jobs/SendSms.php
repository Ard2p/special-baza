<?php

namespace Modules\RestApi\Jobs;

use App\Service\Sms;
use App\Support\SmsNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class SendSms implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;


    protected $phone, $text, $url;
    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($phone, $text, $url = false)
    {
       $this->phone = $phone;
       $this->text = $text;
       $this->url = $url;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $sms = SmsNotification::create([
            'message' => $this->text,
            'phone' => $this->phone,
        ]);
        $q = ($this->url) ? 'tinyurl=1' : '';

        $result = (new Sms())->send_sms($this->phone, $this->text, 0, 0, $sms->id, 0, false, $q);

        $sms->update([
            'status' => json_encode($result)
        ]);
    }
}
