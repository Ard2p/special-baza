<?php

namespace Modules\Dispatcher\Jobs;

use App\Helpers\RequestHelper;
use App\Service\Subscription;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\App;
use Modules\CompanyOffice\Entities\Company\CompanyBranch;
use Modules\Dispatcher\Entities\Lead;

class NewLeadNotifcations implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $lead;
    public $locale;
    public $domain;
    public $tries = 1;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(Lead $lead)
    {
        $this->lead = $lead;
        $this->domain = RequestHelper::requestDomain();
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
        (new Subscription())->newProposalCustomerNotification($this->lead, $this->domain);

        if ($this->lead->publish_type === 'all_contractors') {
            $users = CompanyBranch::query()->where('id', '!=', $this->lead->company_branch_id);

            foreach ($this->lead->userAvailableQuery($users)->get() as $user) {
                (new Subscription())->newProposalNotification($this->lead, $user);
            }
        }

        (new Subscription())->newProposalAdminNotification($this->lead);

    }
}
