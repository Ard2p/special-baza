<?php

namespace App\Service\Google;

use Carbon\Carbon;
use Google\Service\Calendar\Calendar;
use Google\Service\Calendar\Event;
use Google\Service\Calendar\EventDateTime;
use Google\Service\Calendar\EventReminder;
use Google\Service\Calendar\EventReminders;
use Google_Service_Calendar;
use Modules\CompanyOffice\Entities\Company\GoogleEvent;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;

class CalendarService extends Google_Service_Calendar
{
    protected Logger $logger;

    public function __construct($clientOrConfig = [], $rootUrl = null)
    {
        parent::__construct($clientOrConfig, $rootUrl);
        $this->logger = new Logger('google_calendar');
        $this->logger->pushHandler(new StreamHandler(storage_path('logs/google_calendar/' . Carbon::now()->format('Y-m-d') . '.log')));
    }

    public function createEvent(
        int    $type,
               $model,
        string $summary,
        Carbon $dateFrom,
        Carbon $dateTo,
        string $description = null,
        string $address = null,
        string $manager = null,
        string $customer = null,
        string $sum = null,
        string $responsible = null,
        string $employee = null,
               $link = null
    ): Event|bool
    {
        if (!app()->runningInConsole()) {
            $companyBranch = request_branch();

            $res = $this->getAccessToken();

            if (!$res) {
                return false;
            }

            $calendarId = $companyBranch->google_api_settings->calendars->where('type', $type)->first();
            if ($calendarId) {
                $from = new EventDateTime();
                $from->setDateTime($dateFrom->toAtomString());
                $from->setTimeZone($dateFrom->getTimezone()->getName());

                $to = new EventDateTime();
                $to->setDateTime($dateTo->toAtomString());
                $to->setTimeZone($dateFrom->getTimezone()->getName());

                $event = new Event();
                $event->setStart($from);
                $event->setEnd($to);
                $event->setSummary($summary);

                if ($description) {
                    $description .= "\r\n";
                }

                $description .= $responsible ? "\r\nОтветственный: $responsible" : '';
                $description .= $employee ? "\r\nПроверяющий: $employee" : '';
                $description .= $customer ? "\r\nЗаказчик: $customer" : '';
                $description .= $manager ? "\r\nМенеджер: $manager" : '';
                $description .= $sum ? "\r\nСумма: $sum" : '';

                if ($link) {
                    $description = "<a href='$link'>$description</a>";
                }

                $this->logger->debug('Descrption ' . $description);
                $event->setDescription($description);

                $eventReminders = $this->getReminders();
                $event->setReminders($eventReminders);

                if ($address) {
                    $event->setLocation($address);
                }

                try {
                    if (!$model->google_event) {
                        $ev = $this->events->insert($calendarId->google_id, $event);
                        logger()->debug('Event create', ['event' => $ev]);
                    } else {
                        $ev = $this->events->update($calendarId->google_id, $model->google_event->google_event_id, $event);
                        logger()->debug('Event update', ['event' => $ev]);
                    }


                    GoogleEvent::query()->updateOrCreate([
                        'google_event_id' => $ev['id'],
                    ], [
                        'type' => $type,
                        'google_calendar_id' => $calendarId->id,
                        'company_branch_id' => $companyBranch->id,
                        'eventable_id' => $model->id,
                        'eventable_type' => get_class($model)
                    ]);
                    return $ev;
                } catch (\Exception $e) {

                }
            }
            return false;
        }
        return false;
    }


    public function getCalendarList(): array|bool
    {
        $res = $this->getAccessToken();

        if (!$res) {
            return false;
        }
        $calendarList = $this->calendarList->listCalendarList();
        $list = [];
        foreach ($calendarList->getItems() as $calendar) {
            $list[$calendar->id] = $calendar->getSummary();
        }

        return $list;
    }

    public function createCalendar(string $summary): Calendar|bool
    {
        $res = $this->getAccessToken();

        if (!$res) {
            return false;
        }
        $this->logger->debug('Client', ['client' => $this->getClient()]);
        $calendar = new Calendar();
        $calendar->setSummary($summary);
        $res = $this->calendars->insert($calendar);
        $this->logger->debug('Calendar created', ['res' => $res]);
        return $res;
    }

    public function deleteCalendar(string $id): bool
    {
        $res = $this->getAccessToken();

        if (!$res) {
            return false;
        }
        $this->logger->debug('Client', ['client' => $this->getClient()]);
        $this->logger->debug('Delete calendar id: ' . $id);
        $this->logger->debug('Calendar list: ', $this->getCalendarList());
        $res = $this->calendars->delete($id);
        $this->logger->debug('Calendar deleted', ['res' => $res]);
        return (bool)$res;
    }

    public function revokeToken()
    {
        $companyBranch = request_branch();
        $res = $this->getAccessToken();

        if (!$res) {
            return false;
        }
        $res = $this->getClient()->revokeToken($companyBranch->google_api_settings->refresh_token);
        if ($res) {
            return $companyBranch->google_api_settings;
        }
        return false;
    }

    private function getAccessToken(): bool
    {
        $companyBranch = request_branch();
        $settings = $companyBranch->google_api_settings;

        if (!$settings) {
            return false;
        }

        $expiresDate = Carbon::parse($settings->created)->addSeconds($settings->expires_in);

        if (Carbon::now()->diffInSeconds($expiresDate, false) <= 0) {
            $this->logger->debug('Settings', ['settings' => $settings]);
            $this->logger->debug('Refresh', ['refresh' => $settings->refresh_token]);
            $access_token = $this->getClient()->fetchAccessTokenWithRefreshToken($settings->refresh_token);
            $this->logger->debug('Access array', ['access_array' => $access_token]);
            $this->logger->debug('Access token', ['access_token' => $access_token['access_token']]);

            if (isset($access_token['access_token'])) {
                $this->getClient()->setAccessToken($access_token);
                return true;
            }

            return false;
        }

        $this->logger->debug('Settings token', ['access_token' => $settings->access_token]);
        $this->getClient()->setAccessToken($settings->access_token);
        return true;
    }

    /**
     * @return EventReminders
     */
    private function getReminders(): EventReminders
    {
        $remindersMinutes = [
            1440,
            120,
            1
        ];
        $eventReminders = new EventReminders();
        $remindersArray = [];
        foreach ($remindersMinutes as $r) {
            $reminder = new EventReminder();
            $reminder->setMethod('popup');
            $reminder->setMinutes($r);
            $remindersArray[] = $reminder;
        }
        $eventReminders->setUseDefault(false);
        $eventReminders->setOverrides($remindersArray);
        return $eventReminders;
    }
}
