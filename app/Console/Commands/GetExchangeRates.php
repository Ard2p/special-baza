<?php

namespace App\Console\Commands;

use App\Service\RatesService;
use Illuminate\Console\Command;

class GetExchangeRates extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'trans:get-exchange-rates';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Get exchange rates';

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
     * @return void
     */
    public function handle(): void
    {
        $rateService = new RatesService();
        $rateService->getRates(['EUR', 'USD', 'CNY']);
    }

}
