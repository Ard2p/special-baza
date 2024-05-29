<?php

namespace App\Listeners;

use App\Events\ServiceCenterCreatedEvent;
use App\Service\Google\CalendarService;
use Carbon\Carbon;
use Modules\CompanyOffice\Entities\Company\GoogleCalendar;

class SendServiceCenterUpdatedToCalendar
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
    public function handle(ServiceCenterCreatedEvent $event): void
    {
        $center = $event->serviceCenter->load(['company_branch','company_branch.company']);

        $ssl = config('app.ssl');
        $frontUrl = config('app.front_url');
        $companyBranchId = $center->company_branch->id;
        $companyAlias = $center->company_branch->company->alias;

        $this->googleService->createEvent(
            type: GoogleCalendar::TYPE_SERVICE,
            model: $center,
            summary: $center->name,
            dateFrom: Carbon::parse($center->date_from),
            dateTo: Carbon::parse($center->date_to),
            description: "{$center->machinery->name}. $center->description. Статус: $center->status_name.",
            link: "$ssl://$companyAlias.$frontUrl/branch/$companyBranchId/directories/service-center/$center->id"
        );
    }
}
