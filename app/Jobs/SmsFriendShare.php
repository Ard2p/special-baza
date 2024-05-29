<?php

namespace App\Jobs;

use App\Marketing\SmsLink;
use App\Service\Sms;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class SmsFriendShare implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected  $share, $text, $phone;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(SmsLink $share, $phone)
    {
        $url  = $share->link . '?' . http_build_query(['click_friend_sms_id' => $share->id, 'hash' => $share->hash]);
        $this->text = "Посмотри: {$url}";
        $this->share = $share;
        $this->phone = $phone;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $q = 'tinyurl=1';
        $result = (new Sms())->send_sms($this->phone, $this->text, 0, 0, 0, 0, false, $q);

        if($result[1] > 0){
            $this->share->update([
                'is_watch' => 1,
                'watch_at' => Carbon::now(),
            ]);
        }
    }
}
