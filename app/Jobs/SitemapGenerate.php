<?php

namespace App\Jobs;

use App\Http\Controllers\HomeController;
use App\Option;
use App\Service\Subscription;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\Storage;
use Modules\RestApi\Entities\Domain;

class SitemapGenerate implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public $timeout = 1000;

    public function __construct()
    {
        //
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $path = Option::find('sitemap')->value;

        /*HomeController::generateSitemapSeoCategory();
        HomeController::generateSitemapServices();*/
        HomeController::generateSitemapMain();

        $domains = Domain::where('alias', '!=', 'ru')->get();

        foreach ($domains as $domain) {
            HomeController::generateSitemapKinosk($domain);
        }


        $xml = HomeController::siteMapContent()->getContent();
        Storage::disk()->put('sitemaps/sitemaps_kinsok.xml', $xml);
        \Log::info('sitemap complete');
      //  (new Subscription())->siteMapGetenrate();
    }
}
