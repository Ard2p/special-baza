<?php

namespace App\Jobs;

use App\Machinery;
use App\Service\Subscription;
use App\Support\SmsNotification;
use App\User;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class SendNewProposalNotification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;


    protected  $proposal;
    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($proposal)
    {
        $this->proposal = $proposal;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $users = User::query();
        foreach ($this->proposal->types as $type) {

            $users->whereHas('machines', function ($q) use ($type) {
                $q->whereType($type->id);
                $q->where('region_id', $this->proposal->region_id)
                    ->where('city_id',  $this->proposal->city_id)
                    ->checkAvailable($this->proposal->date, $this->proposal->end_date);
            }, '>=', count($this->proposal->types->where('id', $type->id)));
        }

        $users = $users->get();

        foreach ($users as $user){
            $user->sendSmsNotification(SmsNotification::buildNewProposalForContractorText($this->proposal));

            if($user->regional_representative){
                $user->regional_representative->sendSmsNotification(SmsNotification::buildNewProposalForRegionalText($this->proposal));
                (new Subscription())->newProposalNotification($this->proposal,  $user->regional_representative);
            }

            (new Subscription())->newProposalNotification($this->proposal, $user);
        }
    }
}
