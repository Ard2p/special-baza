<?php

namespace App\Providers;

use App\Events\OrderCreatedEvent;
use App\Events\OrderUpdatedEvent;
use App\Events\ServiceCenterCreatedEvent;
use App\Events\ServiceCenterUpdatedEvent;
use App\Http\Controllers\Avito\Events\OrderChangedEvent;
use App\Http\Controllers\Avito\Events\OrderFailedEvent;
use App\Http\Controllers\Avito\Listeners\SendFailedJobToAvito;
use App\Http\Controllers\Avito\Listeners\SendNewStatusToAvito;
use App\Listeners\SendOrderCreatedToCalendar;
use App\Listeners\SendOrderUpdatedToCalendar;
use App\Listeners\SendServiceCenterCreatedToCalendar;
use App\Listeners\SendServiceCenterUpdatedToCalendar;
use Illuminate\Support\Facades\Event;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event listener mappings for the application.
     *
     * @var array
     */
    protected $listen = [
        'App\Events\Event' => [
            'App\Listeners\EventListener',
        ],
        \SocialiteProviders\Manager\SocialiteWasCalled::class => [
            // add your listeners (aka providers) here
            'SocialiteProviders\\VKontakte\\VKontakteExtendSocialite@handle',
        ],
        ServiceCenterCreatedEvent::class => [
            SendServiceCenterCreatedToCalendar::class,
        ],
        ServiceCenterUpdatedEvent::class => [
            SendServiceCenterUpdatedToCalendar::class,
        ],
        OrderCreatedEvent::class => [
            SendOrderCreatedToCalendar::class,
        ],
        OrderUpdatedEvent::class => [
            SendOrderUpdatedToCalendar::class,
        ],
        OrderChangedEvent::class => [
            SendNewStatusToAvito::class,
        ],
        OrderFailedEvent::class => [
            SendFailedJobToAvito::class,
        ],
    ];

    /**
     * Register any events for your application.
     *
     * @return void
     */
    public function boot()
    {
        parent::boot();

        //
    }
}
