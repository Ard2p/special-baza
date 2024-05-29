<?php


namespace App\Service\Scoring;

use App\Service\Scoring\Models\Scoring;
use Carbon\Carbon;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Modules\CompanyOffice\Entities\Company\CompanyBranch;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Cache;

class ScoringService
{
    private const SCORING_TTL = 864000;

    protected array $headers;
    protected Logger $logger;
    private Client $client;
    /**
     * Sberbank constructor.
     */
    public function __construct()
    {
        $key = config('scoring.key');
        $this->headers = [
            'Authorization' => ' Api-Key '.$key
        ];
        $this->client = new Client();

        $this->logger = new Logger('scoring');
        $this->logger->pushHandler(new StreamHandler(storage_path('logs/scoring/'.Carbon::now()->format('Y-m-d').'.log')));
    }


    /**
     * @param  string  $method
     * @param  string  $apiUrl
     * @param  array|null  $params
     * @return mixed
     * @throws GuzzleException
     */
    public function sendRequest(string $method, string $apiUrl, array $params = null)
    {
        $url = config('scoring.url').$apiUrl;
        $request = $this->client
            ->request(
                $method,
                $url,
                [
                    'headers' => $this->headers,
                    'form_params' => $params,
                    'http_errors' => false,
                    'allow_redirects' => false,
                    '_conditional' => [],
                    'debug' => false
                ]
            );
        $result = json_decode($request->getBody()->getContents(), true);
        $this->logger->debug('Url - '.$url);
        $this->logger->debug('Methos - '.$method);
        $this->logger->debug('Params', $params);
        $this->logger->debug('Response', ['result'=>$result]);
        if ($request->getStatusCode() !== 200) {
            $this->logger->error('Error: '.$url, [
                'headers' => $this->headers,
                'params' => $params
            ]);
        }

        return $result;
    }

    /**
     * @throws GuzzleException
     */
    public function physical(
        string $firstName,
        string $lastName,
        string $midName,
        string $birthDate,
        string $passportNumber,
        string $issueDate
    ) {

        $data = [
            'lastName' => $lastName,
            'firstName' => $firstName,
            'midName' => $midName,
            'birthDate' => $birthDate,
            'passportNumber' => $passportNumber,
            'issueDate' => $issueDate,

        ];


        return $this->sendRequest('POST', '/passport_complex', $data);
    }

    public function physicalFssp(
        string $firstName,
        string $lastName,
        string $midName,
        string $birthDate
    ) {

        $data = [
            'lastName' => $lastName,
            'firstName' => $firstName,
            'midName' => $midName,
            'birthDate' => $birthDate,
            //'debug' => 1,
        ];


        return $this->sendRequest('POST', '/fssp', $data);
    }

    /**
     * @throws GuzzleException
     */
    public function legal(string $inn)
    {

        $data = [
            'inn' => $inn,
        ];

        return $this->sendRequest('POST', '/reputation', $data);
    }

    public function checkPhisycal(
        string $firstName,
        string $lastName,
        string $midName,
        string $birthDate,
        string $passportNumber,
        string $issueDate,
        int $customerId = null
    ) {
        $cache = 'Cache';
        $cacheFssp = 'Cache';

        $cacheKey = 'scoring-'.serialize([
                $firstName,
                $lastName,
                $midName,
                $birthDate,
                $passportNumber,
                $issueDate
            ]);
        $cacheFsspKey = 'scoring-fssp'.serialize([
                $firstName,
                $lastName,
                $midName,
                $birthDate,
            ]);

        $response = Cache::get($cacheKey);
        $responseFssp = Cache::get($cacheFsspKey);

        if ($response === null) {
            $cache = 'Direct';
            $response = $this->physical(
                $firstName,
                $lastName,
                $midName,
                $birthDate,
                $passportNumber,
                $issueDate
            );
            Cache::set($cacheKey, $response, self::SCORING_TTL);
        }
        if ($responseFssp === null) {
            $cacheFssp = 'Direct';
            $responseFssp = $this->physicalFssp(
                $firstName,
                $lastName,
                $midName,
                $birthDate,
            );
            Cache::set($cacheFsspKey, $responseFssp, self::SCORING_TTL);
        }

        $scoring = Scoring::query()->create([
            'firstname' => $firstName,
            'lastname' => $lastName,
            'midname' => $midName,
            'birthdate' => $birthDate,
            'passport_number' => $passportNumber,
            'issue_date' => $issueDate,
            'response_json' => $response,
            'result_code' => $response['resultCode'],
            'result_message' => $response['resultMessage'],
            'type' => Scoring::PHYSICAL,
            'format' => $cache,
            'found' => $response['issueDateVerified'],
            'company_branch_id' => request_branch()->id,
            'creator_id' => auth()->user()->id,
            'customer_id' => $customerId,
        ]);
        $scoringFssp = Scoring::query()->create([
            'firstname' => $firstName,
            'lastname' => $lastName,
            'midname' => $midName,
            'birthdate' => $birthDate,
            'passport_number' => $passportNumber,
            'issue_date' => $issueDate,
            'response_json' => $responseFssp,
            'result_code' => $response['resultCode'],
            'result_message' => $response['resultMessage'],
            'type' => Scoring::PHYSICALFSSP,
            'format' => $cacheFssp,
            'found' => $responseFssp['found'],
            'company_branch_id' => request_branch()->id,
            'creator_id' => auth()->user()->id,
            'customer_id' => $customerId,
        ]);
        return $scoring;
    }

    public function checkLegal(string $inn,int $customerId = null)
    {
        $cache = 'Cache';

        $cacheKey = 'scoring-'.serialize($inn);
        $response = Cache::get($cacheKey);

        if ($response === null) {
            $cache = 'Direct';
            $response = $this->legal($inn);
            Cache::set($cacheKey, $response, self::SCORING_TTL);
        }

        $found = $response['TotalItems'] !== 0;

        $scoring = Scoring::query()->create([
            'inn' => $inn,
            'response_json' => $response,
            'type' => Scoring::LEGAL,
            'format' => $cache,
            'found' => $found,
            'company_branch_id' => request_branch()->id,
            'creator_id' => auth()->id(),
            'customer_id' => $customerId,
        ]);
        return $scoring;
    }
}
