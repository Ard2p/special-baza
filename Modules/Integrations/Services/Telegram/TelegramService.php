<?php

namespace Modules\Integrations\Services\Telegram;

use GuzzleHttp\Client;
use GuzzleHttp\RequestOptions;
use Modules\CompanyOffice\Entities\Company\Contact;
use Modules\ContractorOffice\Entities\CompanyWorker;
use Modules\Orders\Entities\OrderComponent;

class TelegramService
{
    static $api = 'https://bot.kinosk.com/api/telegram/';

    private Client $client;

    public function __construct()
    {
        $this->client = new Client(
            [
                'base_uri'    => self::$api,
                'verify'      => false,
                'http_errors' => false,
            ]
        );
    }

    function sendRefreshOrder(OrderComponent $component)
    {
        $phones = $this->getPhones($component);

        foreach ($phones as $phone) {
            $this->client->post('message', [
                RequestOptions::JSON => [
                    'phone' => $phone,
                    'message' => "В сделке #{$component->order_internal_number} изменения. {$component->worker->name}, <b>{$component->date_from} - {$component->date_to}</b>"
                ]
            ]);
        }
    }

    function getPhones(OrderComponent $component)
    {
        /** @var CompanyWorker $driver */
        $driver = $component->driver;

        /** @var Contact $contact */
        $contact = $driver->contacts()->with(['phones'])->whereHas('phones')->first();

        if($contact) {
            $phones = [];
            foreach ($contact->phones as $phone) {
                $phones[] = $phone->phone;
            }

            return $phones;
        }

        return  [];
    }


    function sendOrderInfoToDriver(OrderComponent $component)
    {

        $phones = $this->getPhones($component);

        foreach ($phones as $phone) {
            $this->client->post('message', [
                RequestOptions::JSON => [
                    'phone' => $phone,
                    'message' => "Вам назначена сделка. {$component->worker->name}, <b>{$component->date_from} - {$component->date_to}</b>"
                ]
            ]);
        }
    }

}