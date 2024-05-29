<?php

namespace App\Listeners\Order;

use App\Service\Google\CalendarService;
use Carbon\Carbon;
use Modules\CompanyOffice\Entities\Company\GoogleCalendar;
use Modules\Orders\Entities\Order;

class OrderGoogleCalendar
{
    public function __construct(private CalendarService $googleService)
    {
    }

    public function snedToCalendar(Order $order)
    {
        $ssl = config('app.ssl');
        $frontUrl = config('app.front_url');
        $companyBranchId = $order->company_branch->id;
        $companyAlias = $order->company_branch->company->alias;

        $description = '';
        foreach ($order->components as $component) {
            $description .= "\r\n\r\nПриложение #$component->application_id. {$component->worker?->name}. Статус: ".$this->getComponentStatus($component->status);
        }

        $this->googleService->createEvent(
            type: GoogleCalendar::TYPE_RENT,
            model: $order,
            summary: $order->name,
            dateFrom: Carbon::parse($order->date_from),
            dateTo: Carbon::parse($order->date_to),
            description: $description,
            address: $order?->address,
            manager: $order->lead->manager?->contact_person,
            customer: $order->customer?->company_name,
            sum: number_format($order->amount / 100, 2, ',', ' ').' р.',
            link: "$ssl://$companyAlias.$frontUrl/branch/$companyBranchId/orders/$order->id"
        );
    }

    private function getComponentStatus($status)
    {
        switch ($status) {
            case 'accept':
                return 'Подготовили к аренде';
            case 'reject':
                return 'Отменен';
            case 'done':
                return 'Завершен';
            default:
                return '';
        }
    }
}
