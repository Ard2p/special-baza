<?php

namespace App\Console\Commands;

use App\Machinery;
use Illuminate\Console\Command;
use Modules\Integrations\Entities\WialonVehicle;

class TelematicLastPosition extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'wialon:update-last-position';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        foreach (Machinery::query()->whereNotNull('telematics_type')->whereNotNull('telematics_id')->get() as $vehicle) {
            try {

                $vehicle->telematics->updateLastPosition();

            }catch (\Exception $exception) {
                  \Log::info("TELEMATIC POSITION ERROR {$exception->getMessage()}");
            }

        }
    }
}
