<?php

namespace App\Finance;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\RequestOptions;
use Illuminate\Support\Facades\Log;

class TinkoffMerchantAPI
{
    private $api_url =  'https://rest-api-test.tinkoff.ru/v2/';//'https://securepay.tinkoff.ru/v2/';
    private $terminalKey = '1562655496507';
    private $secretKey = '22u1g0mhsdosv9ng';
    private $paymentId;
    private $status;
    private $error;
    private $response;
    private $paymentUrl;

    const NEW = 'NEW';
    const CONFIRMED = 'CONFIRMED';
    const CANCELED = 'CANCELED';
    const FORM_SHOWED = 'FORM_SHOWED';
    const DEADLINE_EXPIRED = 'DEADLINE_EXPIRED';
    const AUTHORIZING = 'AUTHORIZING';
    const DS_CHECKING = '3DS_CHECKING';
    const DS_CHECKED = '3DS_CHECKED';
    const AUTH_FAIL = 'AUTH_FAIL';
    const AUTHORIZED = 'AUTHORIZED';
    const REVERSING = 'REVERSING';
    const REVERSED = 'REVERSED';
    const CONFIRMING = 'CONFIRMING';
    const REFUNDING = 'REFUNDING';
    const PARTIAL_REFUNDED = 'PARTIAL_REFUNDED';
    const REFUNDED = 'REFUNDED';
    const REJECTED = 'REJECTED';

    const BAD_STATUSES = [
        self::REJECTED,
        self::CANCELED,
        self::DEADLINE_EXPIRED,
        self::FORM_SHOWED,
        self::AUTH_FAIL,

    ];
    const FINAL_STATUSES = [
        self::CONFIRMED,
        self::CANCELED,
        self::REJECTED,
        self::REVERSED,
        self::DEADLINE_EXPIRED,
        self::REFUNDED,
        self::REFUNDING,
        self::PARTIAL_REFUNDED
    ];

    const STATUS_LANG = [
        self::NEW => 'Создан, ожидает оплаты.',
        self::CONFIRMED => 'Подтвержден',
        self::CANCELED => 'Отменен',
        self::FORM_SHOWED => 'Идет процесс оплаты...',
        self::DEADLINE_EXPIRED => 'Истек срок оплаты.',
        self::AUTHORIZING => 'Идет процесс оплаты...',
        self::DS_CHECKING => 'Идет процесс оплаты...',
        self::DS_CHECKED => 'Идет процесс оплаты...',
        self::AUTH_FAIL => 'ОШИБКА АВТОРИЗАЦИИ',
        self::AUTHORIZED => 'Идет процесс оплаты...',
        self::REVERSING => 'Процесс отмены...',
        self::REVERSED => 'Отменен',
        self::CONFIRMING => 'Идет процесс оплаты...',
        self::REFUNDING => 'Идет процесс возвращения.',
        self::PARTIAL_REFUNDED => 'Частично возвращен',
        self::REFUNDED => 'Возвращен',
        self::REJECTED => 'Отменен.',
    ];

        public function __construct()
        {
            $this->api_url = config('app.env') === 'production' ? 'https://securepay.tinkoff.ru/v2/' : 'https://rest-api-test.tinkoff.ru/v2/';

        }

    public function __get($name)
    {
        switch ($name) {
            case 'paymentId':
                return $this->paymentId;
            case 'status':
                return $this->status;
            case 'error':
                return $this->error;
            case 'paymentUrl':
                return $this->paymentUrl;
            case 'response':
                return htmlentities($this->response);
            default:
                if ($this->response) {
                    if ($json = json_decode($this->response, true)) {
                        foreach ($json as $key => $value) {
                            if (strtolower($name) == strtolower($key)) {
                                return $json[$key];
                            }
                        }
                    }
                }

                return false;
        }
    }

    /**
     * @param $args mixed You could use associative array or url params string
     * @return bool
     * @throws HttpException
     */
    public function init($args)
    {
        return $this->buildQuery('Init', $args);
    }


    public function getState($args)
    {
        return $this->buildQuery('GetState', $args);
    }

    public function confirm($args)
    {
        return $this->buildQuery('Confirm', $args);
    }

    public function charge($args)
    {
        return $this->buildQuery('Charge', $args);
    }

    public function addCustomer($args)
    {
        return $this->buildQuery('AddCustomer', $args);
    }

    public function getCustomer($args)
    {
        return $this->buildQuery('GetCustomer', $args);
    }

    public function removeCustomer($args)
    {
        return $this->buildQuery('RemoveCustomer', $args);
    }

    public function getCardList($args)
    {
        return $this->buildQuery('GetCardList', $args);
    }

    public function cancel($args)
    {
        return $this->buildQuery('Cancel', $args);
    }


    public function removeCard($args)
    {
        return $this->buildQuery('RemoveCard', $args);
    }

    /**
     * Builds a query string and call sendRequest method.
     * Could be used to custom API call method.
     *
     * @param string $path API method name
     * @param mixed $args query params
     *
     * @return mixed
     * @throws HttpException
     */
    public function buildQuery($path, $args)
    {
        $url = $this->api_url;
        if (is_array($args)) {
            if (!array_key_exists('TerminalKey', $args)) {
                $args['TerminalKey'] = $this->terminalKey;
            }
            if (!array_key_exists('Token', $args)) {
                $args['Token'] = $this->_genToken($args);
            }
        }
        $url = $this->_combineUrl($url, $path);


        return $this->_sendRequest($url, $args);
    }

    /**
     * Generates Token
     *
     * @param $args
     * @return string
     */
    private function _genToken($args)
    {
        $token = '';
        $args['Password'] = $this->secretKey;
        ksort($args);

        foreach ($args as $arg) {
            if (!is_array($arg)) {
                $token .= $arg;
            }
        }
        $token = hash('sha256', $token);

        return $token;
    }

    /**
     * Combines parts of URL. Simply gets all parameters and puts '/' between
     *
     * @return string
     */
    private function _combineUrl()
    {
        $args = func_get_args();
        $url = '';
        foreach ($args as $arg) {
            if (is_string($arg)) {
                if ($arg[strlen($arg) - 1] !== '/') $arg .= '/';
                $url .= $arg;
            } else {
                continue;
            }
        }

        return $url;
    }

    /**
     * Main method. Call API with params
     *
     * @param $api_url
     * @param $args
     * @return bool|string
     * @throws HttpException
     */
    private function _sendRequest($api_url, $args)
    {
        $this->error = '';

        $client = new Client();

        $request = $client->post($api_url, [
            RequestOptions::JSON => $args,
        ]);
        $out = $request->getBody()->getContents();

        $this->response = $out;
        $json = json_decode($out);

        if ($json) {
            if (@$json->ErrorCode !== "0") {
                $this->error = @$json->Details;
                Log::info(json_encode($args));
                Log::info($out);
                \Log::info($this->error);
            } else {
                $this->paymentUrl = @$json->PaymentURL;
                $this->paymentId = @$json->PaymentId;
                $this->status = @$json->Status;
            }
        }

        return $this;
    }
}