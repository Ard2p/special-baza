<?php


namespace App\Service;


use GuzzleHttp\Client;

class Tilda
{

    private $public_key = '6vzp68pne9kbjp7v2z3t';
    private $private_key = '969xk68diidpddejl470';
    private $url = 'http://api.tildacdn.info/v1/';

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
        return $this->guzzle->get('getpageexport', [
                'query' => array_merge(
                    ['pageid' => $id],
                    $this->guzzle->getConfig('query')
                )
            ]

        )->getBody()->getContents();
    }

}