<?php


namespace App\Service\SpamCheck;


use GuzzleHttp\Client;

class StopForumSpam
{


    private $client;
    private $url = 'http://api.stopforumspam.org/api';


    function __construct()
    {
        $this->client = new Client();
    }

    function IsNotSpamEmail($email)
    {
        return !$this->checkEmail($email);
    }

    function isSpamEmail($email)
    {
        return $this->checkEmail($email);
    }

    private function checkEmail($email)
    {
        $response = $this->client->get($this->url, [
                'query' => ['email' => $email]
            ]

        )->getBody()->getContents();
        $xml = $this->xml2array(simplexml_load_string($response));
        $frequency = $xml['frequency'];

        return ((int)$frequency > 0);
    }

    private function xml2array($xmlObject)
    {
        foreach ((array)$xmlObject as $index => $node)
            $out[$index] = (is_object($node)) ? $this->xml2array($node) : $node;

        return $out;
    }

}