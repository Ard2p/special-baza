<?php

namespace Modules\Telephony\Entities;

use GuzzleHttp\Client;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Modules\Telephony\Events\CallsListen;

class YandexAPI
{
    private $apikey = '86b8fa62-f0e4-4607-9da8-9a30661c138b';
    private $access_token;
    private $client;
    private $apiUrl = 'https://api.yandex.mightycall.ru/api/v3';

    public function __construct()
    {

        $this->client = new Client([
            // Base URI is used with relative requests
            'base_uri' => $this->apiUrl,
        ]);

    }

    function auth()
    {
        $request = $this->client->post('auth/token', [
            'form_params' => [
                'grant_type' => 'client_credentials',
                'client_id' => $this->apikey,
                'client_secret' => '14b6637fa216',
            ],
            'headers' => [
                'x-api-key' => $this->apikey,
            ]
        ]);

        $response = json_decode($request->getBody()->getContents());
        $this->access_token = \Cache::remember('yandex_token', 3600, function () use ($response) {

            return $response->access_token;
        });
    }

    function getCalls()
    {
        return $this->sendData('v3/calls', 'get', ['startUtc' => now()->subDays(100)->format('c')]);
    }

    function getCall($id)
    {
        //Cache::delete('yandex_token');
       return $this->sendData("v3/calls/{$id}");
    }


    private function sendData($url, $method = 'get', $data = [])
    {


        if (!Cache::get('yandex_token')) {
            $this->auth();
        }

        $type  = $method === 'get' ? 'query' : 'form_params';
        $response = $this->client->request($method, $url, [
            'http_errors' => false,
            $type => $data,
            'headers' => [
                'Authorization' => 'bearer ' . Cache::get('yandex_token'),
                'x-api-key' => $this->apikey,
            ]
        ]);
        if($response->getStatusCode() === 401){
            Cache::set('yandex_token', null);
        }

        return json_decode($response->getBody()->getContents(), true);
    }


}
