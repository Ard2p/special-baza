<?php

namespace App\Providers;

use App\Machinery;
use App\Seo\RequestContractor;
use App\Service\EventNotifications;
use App\Service\Subscription;
use App\User;
use Illuminate\Support\ServiceProvider;

class EventSubscription extends ServiceProvider
{
    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        if (app()->runningInConsole()) {
            return;
        }
        $options = \Config::get('global_options');

        RequestContractor::created(function ($m){
            (new EventNotifications())->newRequestContractor($m);
        });
        /*if ($options->where('key', 'subscription_machines')->first()->value == '1') {



        }*/
    }

    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        //
    }
}
