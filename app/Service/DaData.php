<?php

namespace App\Service;

use GuzzleHttp\Client;
use GuzzleHttp\RequestOptions;
use GuzzleHttp\TransferStats;

class DaData
{
    private $api_key = '68a768d9973ba4cd138d3c86e7eab17931240d08';
    private $secret = '94d54f52919c5f391d39ccf2e455df26636fcf6a';

    public $query;

    private $version = '4_1';

    private $client;

    public function __construct($query = [], $cleaner = false)
    {
        $this->query = $query;

        $uri = $cleaner
            ? 'https://cleaner.dadata.ru/api/v1/clean/'
            : "https://suggestions.dadata.ru/suggestions/api/{$this->version}/";

        $this->client = new Client([
            'base_uri' => $uri,
            'headers' => [
                'Authorization' => 'Token ' . $this->api_key,
                'X-Secret' => $this->secret,
                'Content-Type' => 'application/json',
            ],
        ]);
    }

    function getFmsUnit($number)
    {

        $response = $this->client->post("rs/suggest/fms_unit", [
            'http_errors' => false,
            RequestOptions::JSON => [
                'query' => $number,
            ],
        ]);

        return $this->returnResponse($response);
    }

    function cleanName()
    {
        $response = $this->client->post("name", [
            'http_errors' => false,
            RequestOptions::JSON => [
                $this->query,
            ],
        ]);

        return $this->returnResponse($response);
    }

    function checkPassport()
    {
        $response = $this->client->post("passport", [
            'http_errors' => false,
            RequestOptions::JSON => [
                $this->query,
            ],
        ]);

        return $this->returnResponse($response);
    }

    /**
     * @param mixed $query
     */
    public function setQuery($query): void
    {
        $this->query = $query;
    }

    function searchByInn($inn, $type)
    {
        $query = strlen($inn) < 12 ? 'party' : 'party_kz';
        $response = $this->client->post("rs/findById/$query", [
            'http_errors' => false,
            RequestOptions::JSON => [
                'query' => $inn,
                'type' => $type,
            ],
        ]);

        return $this->returnResponse($response);
    }

    function searchByBik($bik)
    {
        $response = $this->client->post("rs/findById/bank", [
            'http_errors' => false,
            RequestOptions::JSON => [
                'query' => $bik,
            ],
        ]);

        return $this->returnResponse($response);
    }

    private function returnResponse($response)
    {
        return json_decode($response->getBody()->getContents());
    }


}
