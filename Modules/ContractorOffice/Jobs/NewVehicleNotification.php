<?php

namespace Modules\ContractorOffice\Jobs;

use App\Machinery;
use App\Service\EventNotifications;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class NewVehicleNotification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;


    public $id, $locale;
    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($id)
    {
        $this->locale = \App::getLocale();
        $this->id = $id;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        \App::setLocale($this->locale);
        $machine = Machinery::query()->findOrFail($this->id);

        (new EventNotifications())->newMachine($machine);


    }
}
