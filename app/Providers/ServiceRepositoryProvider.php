<?php

namespace App\Providers;


use App\Http\Controllers\Avito\Repositories\AvitoRepository;
use App\Http\Controllers\Avito\Repositories\IIntegrationRepository;
use App\Http\Controllers\Avito\Services\AvitoService;
use App\Http\Controllers\Avito\Services\IIntegrationService;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class ServiceRepositoryProvider extends ServiceProvider
{

    /**
     * Register any events for your application.
     *
     * @return void
     */
    public function boot()
    {
        //
    }

    public function register(): void
    {
        //Avito
        $this->app->bind(IIntegrationService::class, AvitoService::class);
        $this->app->bind(IIntegrationRepository::class, AvitoRepository::class);

    }
}
