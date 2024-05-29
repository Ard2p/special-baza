<?php

namespace App\Console\Commands;

use App\Machines\FreeDay;
use Illuminate\Console\Command;

class DeleteOldCalendar extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'calendar:clear-busy';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clear expired busy calendar days';

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
        FreeDay::query()->whereDate('endDate', '<', now()->subDays(7))->where('type', '=', 'busy')->delete();
    }
}
