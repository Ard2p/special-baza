<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Modules\ContractorOffice\Entities\TelematicData;
use Modules\Integrations\Entities\Wialon;

class GetTelematicData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'telematic:pull';

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
        $wialon_connections = Wialon::query()->whereHas('vehicles')
            ->with(['vehicles' => function($q) {
                $q->whereHas('transbaza_vehicle');
            }])
            ->get();

        $subDay = 1;

        $period_from = now()
            ->subDay($subDay)
            ->startOfDay();
        $period_to = now()->subDay($subDay)
            ->endOfDay();

        foreach ($wialon_connections as  $connection)
        {
            $resource = ($connection->getResourceByName($connection->login));
            foreach ($connection->vehicles as $vehicle)
            {
                try {

                    $report = $connection->getUnitReport($vehicle->wialon_id, $resource['id'],
                        $period_from->getTimestamp(),
                        $period_to->getTimestamp()
                    )->getResult();

                    TelematicData::create(array_merge($report->toArray(), [
                        'telematic_vehicle_id' => $vehicle->id,
                        'period_from' => $period_from,
                        'period_to' => $period_to,
                    ]));

                }catch (\Exception $exception) {
                    Log::info("Не удалось получить данные для {$vehicle->id} {$exception->getMessage()}");

                }

            }

        }
    }
}
