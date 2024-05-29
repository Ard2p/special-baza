<?php

namespace App\Jobs\Marketing;

use App\Mail\SubscribeMail;
use App\User\SendingSubscribe;
use App\User\SubscribeTemplate;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\Mail;

class Subscribe implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;


    protected $template, $users;
    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(SubscribeTemplate $template, $users)
    {
       $this->template = $template;
       $this->users = $users;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        foreach ($this->users as $user){
            $sending = SendingSubscribe::create([
               'user_id' => $user->id,
               'subscribe_template_id' => $this->template->id,
               'confirm_status' => 0,
               'hash' => str_random(8),
            ]);

            Mail::to($user->email)->queue(new SubscribeMail($this->template, $this->template->subscribe->name, $sending));

            $user->addNotificationHistory('notification', 'email');
        }
    }
}
