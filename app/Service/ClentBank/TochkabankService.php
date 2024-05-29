<?php

namespace App\Service\ClentBank;

use Carbon\Carbon;
use GuzzleHttp\Client;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;

class TochkabankService
{

    public $client, $headers, $logger, $token, $clientId;

    public function __construct()
    {
        $data = request_branch()->client_bank_settings->where('name', 'tochka')->first()->parameters;

        $this->clientId = $data['client_id'];
        $this->token = $data['client_token'];

        $this->appUrl = config('app.url');

        $this->client = new Client();
        $this->logger = new Logger('order');
        $this->logger->pushHandler(new StreamHandler(storage_path('logs/clientbank/tochka/'.Carbon::now()->format('Y-m-d').'.log')));
    }

    public function sendRequest($method, $apiUrl, $params = null)
    {
        $params = array_merge($params, []);

        $this->headers = [
            'Accept' => 'application/json; charset=utf-8',
            'Authorization' => "Bearer ".$this->token
        ];
        $url = config('client_bank.tochka_url').$apiUrl;

        $this->logger->debug("$method - ".$url);
        $this->logger->debug('Params: ', $params);
        $this->logger->debug('Headers: ', $this->headers);

        $request = $this->client
            ->request(
                $method,
                $url,
                [
                    'headers' => $this->headers,
                    'json' => ($method != 'GET') ? $params : [],
                    'query' => ($method == 'GET') ? $params : [],
                    'http_errors' => false,
                    'allow_redirects' => false,
                    '_conditional' => [],
                    'debug' => false
                ]
            );
        $data = $request->getBody()->getContents();

        $this->logger->debug('Response: '.$data);

        $result = json_decode($data, true);

        if (isset($result['code'])) {
            $this->logger->error('Url: '.$url);
            $this->logger->error('Query', $params);
            $this->logger->error('Error', $result);
            return false;
        }

        return $result;
    }

    public function getWebhooks()
    {

        $response = $this->sendRequest(
            'GET',
            "/webhook/v1.0/".$this->clientId,
            [],
        );

        return $response;
    }

    public function createWebhook()
    {
        $params = [
            "webhooksList" => [
                "incomingPayment"
            ],
            "url" => $this->appUrl.'/hook/'.request_branch()->id
        ];

        $response = $this->sendRequest(
            'PUT',
            "/webhook/v1.0/".$this->clientId,
            $params,
        );

        return $response;
    }

    public function updateWebhook()
    {
        $params = [
            "webhooksList" => [
                "incomingPayment"
            ],
            "url" => $this->appUrl.'/hook/'.request_branch()->id
        ];

        $response = $this->sendRequest(
            'POST',
            "/webhook/v1.0/".$this->clientId,
            $params,
        );

        return $response;
    }

    public function sendWebhook()
    {
        $params = [
            "webhookType" => "incomingPayment"
        ];

        $response = $this->sendRequest(
            'POST',
            "/webhook/v1.0/".$this->clientId."/test_send",
            $params,
        );

        return $response;
    }

    public function deleteWebhook()
    {
        $response = $this->sendRequest(
            'DELETE',
            "/webhook/v1.0/".$this->clientId,
            [],
        );

        return $response;
    }

    public function getTransactions()
    {

        $response = $this->sendRequest(
            'GET',
            "/open-banking/v1.0/statements",
            [],
        );

        return $response;
    }

    public function initStatment(string $accountId, string $dateFrom, string $dateTo)
    {
        $params = [
            "Data" => [
                "Statement" => [
                    "accountId" => $accountId,
                    "startDateTime" => $dateFrom,
                    "endDateTime" => $dateTo
                ]
            ]
        ];

        $response = $this->sendRequest(
            'POST',
            "/open-banking/v1.0/statements",
            $params,
        );

        return $response;
    }

    public function getAccounts()
    {
        $params = [];

        $response = $this->sendRequest(
            'GET',
            "/open-banking/v1.0/accounts",
            $params,
        );

        return $response;
    }

    public function initAllStatements()
    {
        $response = $this->getAccounts();

        $dateFrom = Carbon::now()->format('Y-m-d');
        $dateTo = Carbon::now()->format('Y-m-d');

        foreach ($response['Data']['Account'] as $account) {
            $this->initStatment($account['accountId'], $dateFrom, $dateTo);
        }
    }

}
