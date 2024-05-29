<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\Orders\Entities\Service\ServiceCenter;

class ServiceCenterUpdatedEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;


    public ServiceCenter $serviceCenter;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(ServiceCenter $serviceCenter)
    {
        $this->serviceCenter = $serviceCenter;
    }

}
