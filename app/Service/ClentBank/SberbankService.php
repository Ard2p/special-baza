<?php

namespace App\Service\ClentBank;

use Carbon\Carbon;
use GuzzleHttp\Client;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;

class SberbankService
{

    public $client, $headers, $logger;

    public function __construct()
    {
        $this->client = new Client();
        $this->logger = new Logger('order');
        $this->logger->pushHandler(new StreamHandler(storage_path('logs/clientbank/sber/'.Carbon::now()->format('Y-m-d').'.log')));
    }

    public function sendRequest($method, $apiUrl, $params = null)
    {
        $params = array_merge($params, [
            'accountNumber' => '40702810038120042645',
            'statementDate' => '2022-11-30',
        ]);

        $this->headers = [
            'Accept' => 'application/json',
            'Authorization' => "Bearer 9e1efef1-e584-4c6f-9da3-34a04d203308-1"
        ];
        $url = config('client_bank.sber_url').$apiUrl;

        $this->logger->debug('Url: '.$url);
        $this->logger->debug('Params: ', $params);
        $this->logger->debug('Headers: ', $this->headers);

        $request = $this->client
            ->request(
                $method,
                $url,
                [
                    'headers' => $this->headers,
                    'form_params' => ($method != 'GET') ? $params : [],
                    'query' => ($method == 'GET') ? $params : [],
                    'http_errors' => false,
                    'allow_redirects' => true,
                    '_conditional' => [],
                    'debug' => true
                ]
            );

        $this->logger->debug('Response: '.$request->getBody()->getContents());
        $result = json_decode($request->getBody()->getContents(), true);

        if (isset($result['errorCode']) && intval($result['errorCode']) !== 0) {
            $this->logger->error('Url: '.config('payment.url').$apiUrl);
            $this->logger->error('Query', $params);
            $this->logger->error('Error', $result);
            return false;
        }

        return $result;
    }

    public function getTransactions()
    {
        $params = [
            'page' => 1
        ];

        $response = $this->sendRequest(
            'GET',
            "/statement/transactions",
            $params,
        );
//        if ($response) {
//            $this->logger->debug('Order registered', $response);
//        }
        return $response;
    }

}
