<?php


namespace App\Service;


use GuzzleHttp\Client;

class Yandex
{

    private $id = '8fb93604661e4845b3163ffd3cf9de96';
    private $pass = 'a79cf0253bf24d64b716f4eee5013937';
    private $url = '';

    private $guzzle;

    function __construct()
    {
        $this->guzzle = new Client(
            [
                'base_uri' => $this->url,

                'query' => [
                    'publickey' => $this->public_key,
                    'secretkey' => $this->private_key
                ]


            ]
        );

    }

    function pageExport($id)
    {

    }

}