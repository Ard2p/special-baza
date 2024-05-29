<?php

namespace App\Providers;

use App\Service\Google\CalendarService;
use App\Service\RequestBranch;
use Google_Client;
use Google_Service_Calendar;
use Illuminate\Support\ServiceProvider;
use Modules\CompanyOffice\Entities\Company\CompanyBranch;

class GoogleCalendarServiceProvider extends ServiceProvider
{

    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        try {
            $this->app->singleton(Google_Client::class, function ($app) {
                $client = new Google_Client();
                $client->setApplicationName(config('app.name'));
                $client->setAuthConfig(storage_path('app/google-calendar/oauth-credentials.json'));
                $client->addScope(Google_Service_Calendar::CALENDAR);
                $client->setAccessType('offline');
                $client->setPrompt('consent');
                $redirect_uri = config('services.google.redirect');
                $client->setRedirectUri($redirect_uri);
                return $client;
            });

            $this->app->singleton(CalendarService::class, function ($app) {
                $client = $this->app->make(Google_Client::class);
                return new CalendarService($client);
            });
        }catch(\Exception $e){
            logger()->error('Failed init google calendar');
        }
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {

    }
}
