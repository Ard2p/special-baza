<?php

namespace App\Console\Commands;

use App\System\Audit;
use Illuminate\Console\Command;

class ClearAudits extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'audits:clear';

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
        Audit::where('created_at', '<', now()->subDays(30))->delete();
        Audit::where('created_at', '<', now()->subDays(1))
            ->where('old_values', '=', '[]')
            ->where('new_values', '=', '[]')
            ->delete();
    }
}
