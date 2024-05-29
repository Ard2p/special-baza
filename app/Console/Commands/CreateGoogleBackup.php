<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class CreateGoogleBackup extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'update_google_backup';

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
  /*      $files = Storage::drive('backup')->allFiles();
        $searchword = 'transbaza.' . now()->format('Y-m-d');
        $matches = array_filter($files, function ($var) use ($searchword) {

            return Str::contains($var, $searchword);
        });
        if (!empty($matches)) {
            $file = array_pop($matches);

        } else {
            dd('fail');
        }*/
        $format = 'Y_m_d';
        $dt = now()->format($format);



        $file = "dump_all_{$dt}.sql";

        $filePath = storage_path($file);

        $prevDt = now()->subDays(2)->format($format);
        $prevFile = "dump_all_{$prevDt}.sql";
        $prevPath = storage_path($prevFile);

        \Spatie\DbDumper\Databases\MySql::create()
            // ->setDbName('transbaza_test')
            ->setUserName(config('database.connections.mysql.username'))
            ->setPassword(config('database.connections.mysql.password'))
            ->setHost(config('database.connections.mysql.host'))
            ->addExtraOption('--all-databases')
            ->dumpToFile($filePath);

        if(\File::exists($prevPath)) {
            File::delete($prevPath);
        }

        $g = new \App\Service\GoogleDrive();
        $id = $g->updateFile('1zVK97tQnxOHap3MF3tK-hq_pbvQGbUTZ', storage_path() . '/', $file);
        Log::info('backup updated');
    }
}
