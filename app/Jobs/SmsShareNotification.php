<?php

namespace App\Jobs;

use App\Marketing\ShareList;
use App\Service\Sms;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class SmsShareNotification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected  $share, $text;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(ShareList $share)
    {
        $url  = $share->url . '?' . http_build_query(['click_id' => $share->id]);
        $this->text = "Посмотри: {$url}";
        $this->share = $share;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $q = 'tinyurl=1';
        $result = (new Sms())->send_sms($this->share->phone, $this->text, 0, 0, 0, 0, false, $q);

        if($result[1] > 0){
            $this->share->update([
                'is_watch' => 1,
                'watch_at' => Carbon::now(),
            ]);
        }

    }
}
