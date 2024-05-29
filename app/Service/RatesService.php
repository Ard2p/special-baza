<?php


namespace App\Service;

use App\Rate;
use App\Service\Scoring\Models\Scoring;
use Carbon\Carbon;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Modules\CompanyOffice\Entities\Company\CompanyBranch;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Cache;

class RatesService
{
    protected array $headers;
    protected Logger $logger;
    private Client $client;

    /**
     * Sberbank constructor.
     */
    public function __construct()
    {

        $this->headers = [

        ];
        $this->client = new Client();

        $this->logger = new Logger('rates');
        $this->logger->pushHandler(new StreamHandler(storage_path('logs/rates/'.Carbon::now()->format('Y-m-d').'.log')));
    }


    /**
     * @param  string  $method
     * @param  string  $apiUrl
     * @param  array|null  $params
     * @return mixed
     * @throws GuzzleException
     */
    public function sendRequest(string $method, string $apiUrl, array $params = null, array $courrencies = [])
    {
        $url = 'http://www.cbr.ru/scripts/XML_daily.asp';
        $request = $this->client
            ->request(
                $method,
                $url,
                [
                    'headers' => $this->headers,
                    'query' => $params,
                    'http_errors' => false,
                    'allow_redirects' => false,
                    '_conditional' => [],
                    'debug' => false
                ]
            );

        $data = [];

        $content_currency = simplexml_load_string($request->getBody()->getContents());

        foreach ($courrencies as $currency) {
            $data[$currency] = floatval(number_format(str_replace(',', '.',
                $content_currency->xpath('Valute[CharCode="'.$currency.'"]')[0]->Value), 2));
        }

        return $data;
    }

    public function getRates(string|array $currencies)
    {
        if (is_string($currencies)) {
            $currencies = [$currencies];
        }
        $rates = Rate::query()
            ->whereIn('to_currency', $currencies)
            ->where('date', Carbon::now()->format('Y-m-d'))
            ->get()->pluck('rate', 'to_currency')->toArray();

        if (empty($rates)) {
            $params = [
                'date_req' => Carbon::now()->format('d/m/Y')
            ];

            $rates = $this->sendRequest('GET', '', $params, $currencies);
            foreach ($rates as $currency => $rate) {
                Rate::query()->updateOrCreate([
                    'from_currency' => 'RUB',
                    'to_currency' => $currency,
                    'date' => Carbon::now()->format('Y-m-d'),
                ], [
                    'rate' => $rate
                ]);
            }
        }

        return $rates;
    }
}
