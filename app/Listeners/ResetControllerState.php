<?php

namespace App\Listeners;

use Illuminate\Routing\Router;

class ResetControllerState
{
    /**
     * Handle the event.
     *
     * @param  mixed  $event
     * @return void
     */
    public function handle($event): void
    {
        /** @var Router $router */
        $router = $event->sandbox->make(Router::class);

        $currentRoute = $router->current();

        if($currentRoute && $currentRoute->controller) {
            $currentRoute->controller->resetMiddleware();
            $currentRoute->flushController();
            logger('middleware:', $currentRoute->computedMiddleware);
            $currentRoute->computedMiddleware = [];
            \App::forgetInstance($currentRoute);
        }


    }
}