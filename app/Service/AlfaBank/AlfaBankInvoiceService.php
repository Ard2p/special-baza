<?php

namespace App\Service\AlfaBank;

use App\AppSetting;
use App\Http\Controllers\Avito\Events\OrderChangedEvent;
use App\Http\Controllers\Avito\Models\AvitoOrder;
use App\Jobs\AvitoNotificaion;
use App\Service\AlfaBank\Dto\PaymentReceived;
use Carbon\Carbon;
use GuzzleHttp\Client;
use Illuminate\Http\Request;
use Modules\Dispatcher\Entities\DispatcherInvoice;
use Modules\Orders\Entities\Payments\InvoicePay;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;

class AlfaBankInvoiceService
{

    public $client, $headers, $logger;
    private $alfaSettings;
    private $accessToken;
    private $debug = false;

    public function __construct()
    {

        $this->client = new Client();
        $this->logger = new Logger('alfa-bank');
        $this->logger->pushHandler(new StreamHandler(storage_path('logs/alfa-bank/' . now()->format('Y-m-d') . '.log')));

//        $this->alfaSettings = AppSetting::query()->where('type', 'alfa')->first();
//
//        if ($this->alfaSettings && $this->alfaSettings->data['code'] !== config('services.alfa.code')) {
//            $this->alfaSettings->delete();
//            $this->alfaSettings = null;
//        }
//
//        if (!$this->alfaSettings) {
//            $this->alfaSettings = AppSetting::query()->create([
//                'type' => 'alfa',
//                'data' => [
//                    'code' => config('services.alfa.code'),
//                    'redirect_uri' => config('services.alfa.redirect_uri'),
//                    'client_id' => config('services.alfa.client_id')
//                ]
//            ]);
//            $this->getClientSecret();
//        }
//
//        $this->accessToken = $this->getAccessToken();
    }

    public function sendRequest($method, $apiUrl, $params = [], $query = [], $json = [])
    {
        if ($this->accessToken) {
            $this->headers['Authorization'] = "Bearer " . $this->accessToken;
        }

        $this->headers['Accept'] = "application/json";

        $url = $this->getUrl($apiUrl);
        try {

            if (!empty($json)) {
                $this->headers['Content-Type'] = "application/json";
                $json = [
                    'json' => $json
                ];
            }
            if ($this->debug) {
                dump($url);
                dump($method);
                dump($this->headers);
            }
            $request = $this->client
                ->request(
                    $method,
                    $url,
                    [
                        'cert' => [storage_path('app/alfa/test_pkcs.p12'), '4321'],
                        'headers' => $this->headers,
                        'form_params' => $params,
                        'query' => $query,
                        'http_errors' => true,
                        'allow_redirects' => true,
                        '_conditional' => [],
                        'debug' => $this->debug,
                        ...$json
                    ],
                );
            $result = json_decode($request->getBody()->getContents(), true);

            return $result;
        } catch (\Exception $e) {
            $this->logger->debug('Error ' . $e->getMessage() . ' ' . $e->getLine() . ' ' . $e->getFile(), $e->getTrace());
        }
        return null;
    }

    public function processPayment(PaymentReceived $paymentDto): void
    {
        preg_match('/R-(\d+)/', $paymentDto->data->paymentPurpose, $matches);
        if($paymentDto->actionType !== 'create'){
            return;
        }
        if (count($matches) <= 1) {
            return;
            //TODO message
//            $message = "Заказ с Авито оплачен клиентом. Не был указан номер сделки";
//            dispatch(new AvitoNotificaion($order,$message))->delay(Carbon::now()->addSeconds(5));
        }

        $avitoOrderId = $matches[0];

        $order = AvitoOrder::query()->where('avito_order_id', $avitoOrderId)->latest()->first()->order;
        $invoice = $order->invoices->where('is_payed', 0)->where('type', 'avito')->first();

        $order = $invoice->owner;
        if ($invoice->paid_sum >= $invoice->sum) {
            return;
        }
        $pay = new InvoicePay([
            'type' => 'cashless',
            'date' => Carbon::now()->format('Y-m-d H:i:s'),
            'sum' => $paymentDto->data->amountRub->amount,
            'operation' => 'in',
            'method' => 'bank',
            'tax_percent' => 0,
            'tax' => 0,
        ]);
        $invoice->pays()->save($pay);
        OrderChangedEvent::dispatch($invoice->owner, AvitoOrder::STATUS_PREPAID);
        $message = "Заказ с Авито #$order->external_id оплачен клиентом.";
        dispatch(new AvitoNotificaion($order, $message))->delay(Carbon::now()->addSeconds(5));
    }

    /**
     * @return mixed
     */
    public function rewriteWebhook(): mixed
    {
        $webhooks = $this->listWebhooks()['items'];
        if (!empty($webhooks)) {
            $this->deleteWebhook($webhooks[0]['id']);
        }
        return $this->registerWebhook();
    }

    public function registerWebhook()
    {
        $params = [
            'object' => 'ul_transaction_default',
            'callbackUri' => config('services.alfa.callback_url'),
            'version' => 1,
            'data' => [
                'accounts' => [
                    config('services.alfa.account_number')
                ]
            ]
        ];

        return $this->sendRequest('POST', '/v1/webhooks', json: $params);
    }

    public function testWebhook()
    {
        $params = [
            'object' => 'ul_transaction_default',
        ];

        return $this->sendRequest('POST', '/v1/webhooks-events', $params);
    }

    public function deleteWebhook(string $webhookId)
    {
        $params = [
            'object' => 'ul_transaction_default',
        ];

        return $this->sendRequest('DELETE', '/v1/webhooks/' . $webhookId, query: $params);
    }

    public function listWebhooks()
    {
        $params = [
            'object' => 'ul_transaction_default',
            'limit' => 1,
            'offset' => 0,
        ];
        return $this->sendRequest('GET', '/v1/webhooks', query: $params);
    }

    private function getAccessToken()
    {
        if ($this->checkForRefresh()) {
            return $this->alfaSettings->data['access_token'];
        }

        $grant = isset($this->alfaSettings->data['refresh_token']) ? 'refresh_token' : 'authorization_code';

        $params = [
            ...$this->alfaSettings->data,
            'grant_type' => $grant
        ];

        $result = $this->sendRequest('POST', '/token', $params);
        if (!$result) {
            return '';
        }
        $this->updateSettings([
            'refresh_token' => $result['refresh_token'],
            'expires_in' => $result['expires_in'],
            'created_at' => now()->format('Y-m-d H:i:s'),
            'access_token' => $result['access_token']
        ]);

        return $result['access_token'];
    }

    private function getClientSecret(): void
    {
        $clientSecret = config('services.alfa.client_id');
        $response = $this->sendRequest('POST', "/clients/$clientSecret/client-secret");
        $this->updateSettings([
            'client_secret' => $response['clientSecret']
        ]);
    }

    /**
     * @return \Illuminate\Config\Repository|\Illuminate\Contracts\Foundation\Application|mixed
     */
    private function getToken(): mixed
    {
        return config('gateway.token');
    }

    /**
     * @param $apiUrl
     * @return string
     */
    private function getUrl($apiUrl): string
    {
        return config('services.alfa.url') . $apiUrl;
    }

    private function updateSettings($data)
    {
        $res = [
            ...$data,
            ...$this->alfaSettings->data
        ];

        $this->alfaSettings->update([
            'data' => $res
        ]);
    }

    private function checkForRefresh()
    {
        $data = $this->alfaSettings->data;
        if (
            isset($data['access_token'])
            && Carbon::parse($data['created_at'])->addSeconds(3600 - 1)->gte(now())
        ) {
            return true;
        }
        return false;
    }
}
