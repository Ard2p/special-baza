<?php

namespace Modules\AdminOffice\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Modules\Telephony\Entities\Call;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;

class GrabCalls extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'calls:grab';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Grab calls from yandex.';

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
        $calls = Call::whereGrabbed(0)->get();

        foreach ($calls as $call){
            $call->updateYandexData();
        }

        $for_delete = Call::where('created_at', '<', now()->subMonth(2))->get();

        foreach ($for_delete as $call) {
            if($call->record_name) {

                $path = storage_path("calls/{$call->record_name}");
                if(File::exists($path)) {
                    File::delete($path);
                }
            }
            $call->delete();
        }

    }

}
