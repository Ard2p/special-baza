<?php

namespace App\Service\Avito;

use App\Http\Controllers\Avito\Models\AvitoOrder;
use Cache;
use Exception;
use GuzzleHttp\Client;
use Illuminate\Database\Eloquent\Model;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;

class AvitoApiService
{

    private Client $client;
    private array $headers;
    private bool $enabled;
    private Logger $logger;

    public function __construct()
    {
        $this->enabled = config('services.avito.enabled');
        $this->client = new Client();
        $this->logger = new Logger('avito-api');
        $this->logger->pushHandler(new StreamHandler(storage_path('logs/avito-api/' . now()->format('Y-m-d') . '.log')));
    }

    public function sendRequest($method, $apiUrl, $params = [], $contentType = "application/x-www-form-urlencoded")
    {
        $this->headers['Accept'] = "application/json";
        $this->headers['Content-Type'] = $contentType;

        $url = $this->getUrl($apiUrl);
        $this->logger->debug('Method ', ['method' => $method]);
        $this->logger->debug('Url ', ['url' => $url]);
        $this->logger->debug('Headers ', ['headers' => $this->headers]);
        $this->logger->debug('Body ', ['body' => $params]);
        if (!$this->enabled) {
            return null;
        }
        try {
            $data = [
                'headers' => $this->headers,
                'http_errors' => false,
                'allow_redirects' => true,
                '_conditional' => [],
                'debug' => false
            ];

            if ($contentType === "application/x-www-form-urlencoded") {
                $data['form_params'] = $params;
            } else {
                $data['json'] = $params;
            }

            $request = $this->client->request($method, $url, $data);
            $result = json_decode($request->getBody()->getContents(), true);
            $this->logger->debug('Debug ', ['result' => $result]);
            return $result;
        } catch (Exception $e) {
            $this->logger->debug('Error ' . $e->getMessage() . ' ' . $e->getLine() . ' ' . $e->getFile(), $e->getTrace());
        }
        return null;
    }

    public function sendOrderStatusChanged(AvitoOrder|Model $avitoOrder, int $statusFrom, int $statusTo)
    {
        $this->headers['Authorization'] = "Bearer " . $this->getToken();

        $params = [
            'avito_order_id' => $avitoOrder->avito_order_id,
            'status_from' => "$statusFrom",
            'status_to' => "$statusTo",
        ];


        return $this->sendRequest('POST', '/machinery-rental/update_order_status/', $params, "application/json");
    }

    private function getAuthToken()
    {
        $accessToken = Cache::get('avito_token');

        if ($accessToken) {
            return $accessToken;
        }

        $params = [
            ...$this->getKeys(),
            'grant_type' => 'client_credentials'
        ];

        $response = $this->sendRequest('POST', '/token', $params);

        Cache::set('avito_token', $response['access_token'], now()->addHours(23));

        return $response['access_token'];
    }

    private function refreshToken(string $refreshToken)
    {
        $params = [
            ...$this->getKeys(),
            'grant_type' => 'refresh_token',
            'refresh_token' => $refreshToken
        ];
        return $this->sendRequest('POST', '/token', $params);
    }


    public function getToken(): string
    {
        if (!$this->enabled) {
            return 'access';
        }
        return $this->getAuthToken();
    }

    /**
     * @param $apiUrl
     * @return string
     */
    private function getUrl($apiUrl): string
    {
        return config('services.avito.url') . $apiUrl;
    }

    private function getKeys(): array
    {
        return [
            'client_id' => config('services.avito.client_id'),
            'client_secret' => config('services.avito.client_secret')
        ];
    }

}
