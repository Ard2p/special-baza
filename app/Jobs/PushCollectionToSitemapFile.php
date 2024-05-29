<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class PushCollectionToSitemapFile implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;


    public $filename, $data, $timeout = 1000;
    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($data, $filename)
    {
      $this->data = $data;
      $this->filename = $filename;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        \Storage::disk()->put($this->filename, $this->data);
    }
}
