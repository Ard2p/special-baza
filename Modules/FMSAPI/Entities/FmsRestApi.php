<?php

namespace Modules\FMSAPI\Entities;


use App\Machines\FreeDay;
use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\RequestOptions;
use Illuminate\Support\Facades\DB;
use Psr\Http\Message\ResponseInterface;

class FmsRestApi
{
    private $login = 'info@trans-baza.ru';
    private $password = 'trans-baza.ru';
    private $url = 'https://demo007.c-cars.tech/api/v1/user-management/login';
    private $events_url = 'https://demo007.c-cars.tech';
    public $client;

    private $data = [];

    function mapCalendar($calendar)
    {
        return [
            'id' => $calendar->id,
            'date_from' => $calendar->start,
            'date_to' => $calendar->end,
            'type' => $calendar->type,
            'order_id' => $calendar->proposal_id,
            'vehicle_id' => $calendar->machine_id,
            'contractor_id' => $calendar->proposal->winner_offer->user_id ?? null,

        ];
    }


    private function getClient($token)
    {
        return new Client([
            'base_uri' => $this->events_url,
            'headers' => [
                'Accept' => 'application/json',
                'Authorization' => 'Bearer ' . $token,
                'Content-Type' => 'application/json',
            ],
        ]);
    }


    function createCalendar(FreeDay $calendar)
    {
        $data = $this->mapCalendar($calendar);

        $this->sendData('events', 'create', $data, $calendar);

        return $this;
    }

    function updateCalendar(FreeDay $calendar)
    {
        $data = $this->mapCalendar($calendar);

        $this->sendData('events', 'update', $data, $calendar);

        return $this;
    }

    function deleteCalendar(FreeDay $calendar)
    {
        $data = $this->mapCalendar($calendar);

        $this->sendData('events', 'delete', $data, $calendar);


       return $this;
    }


    private function sendData($name, $action, $data, $calendar)
    {
        $data = ['type' => $name, 'action' => $action, 'data' => $data];
        try {
            $integrations = $calendar->machine->user->integrations;
            $http = new Client();
            foreach ($integrations as $integration){

                if(!$integration->event_back_url){
                    continue;

                }
                \Log::info(json_encode($data));
                \Log::info($integration->event_back_url);
                $response = $http->postAsync($integration->event_back_url, [
                    RequestOptions::JSON => $data,
                ])->then(function (ResponseInterface $response){
                    \Log::info(($response->getBody()->getContents()));
                });

                $response->wait();
            }

        } catch (Exception $e) {
            \Log::error($e->getMessage());

        }
    }

}
