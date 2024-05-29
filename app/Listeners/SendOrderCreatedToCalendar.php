<?php

namespace App\Listeners;

use App\Events\OrderCreatedEvent;
use App\Listeners\Order\OrderGoogleCalendar;
use App\Service\Google\CalendarService;
use Carbon\Carbon;
use Modules\CompanyOffice\Entities\Company\GoogleCalendar;

class SendOrderCreatedToCalendar
{

    public function __construct(private CalendarService $googleService)
    {
    }

    /**
     * Handle the event.
     *
     * @param  mixed  $event
     * @return void
     */
    public function handle(OrderCreatedEvent $event): void
    {
        $order = $event->order->load(['company_branch','company_branch.company']);

        $ogc = new OrderGoogleCalendar($this->googleService);
        $ogc->snedToCalendar($order);
    }
}
