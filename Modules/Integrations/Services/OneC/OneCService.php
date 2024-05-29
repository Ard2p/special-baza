<?php


namespace Modules\Integrations\Services\OneC;


use App\Machinery;
use Carbon\Carbon;
use GuzzleHttp\Client;
use GuzzleHttp\RequestOptions;
use Modules\CompanyOffice\Entities\Company\CompanyBranch;
use Modules\Dispatcher\Entities\Customer;
use Modules\Dispatcher\Entities\DispatcherInvoice;
use Modules\Dispatcher\Entities\Documents\Contract;
use Modules\Integrations\Entities\OneC\Connector;
use Modules\PartsWarehouse\Entities\Stock\Stock;

class OneCService
{

    /** @var CompanyBranch $companyBranch */
    private $companyBranch;
    public $client;
    private $baseUri = 'https://1c.kinosk.com';

    public function __construct(CompanyBranch $companyBranch)
    {
        $this->companyBranch = $companyBranch;

        if ($this->companyBranch->OneCConnection) {
            $this->client = new Client([
                'base_uri' => $this->baseUri,
                'verify' => false,
                'http_errors' => false,
                'query' => [
                    'uuid' => $this->companyBranch->OneCConnection->onec_id ?? '',
                ]
            ]);
        }
    }

    function getConnection()
    {
        if (!$this->client) {
            return response()->json(['status'=>false, 'message' => 'Not enabled'], 400);
        }
        $this->client = new Client([
            'base_uri' => $this->baseUri,
            'verify' => false,
            'http_errors' => false,
            'query' => [
                'uuid' => $this->companyBranch->OneCConnection->onec_id ?? '',
            ]
        ]);


        $response = $this->client->get('connect');

        return response()->json(json_decode($response->getBody()->getContents(), true), $response->getStatusCode());
    }

    public function addConnection($login, $password, $url, $delivery = '', $defaultVendorCode = '', $pledgeVendorCode = '', $data = [])
    {
        $this->client = new Client([
            'base_uri' => $this->baseUri,
            'http_errors' => false,
            'verify' => false,
            'query' => [
                'uuid' => $this->companyBranch->OneCConnection->onec_id ?? '',
            ]
        ]);

        $response = $this->client->post('connect', [
            RequestOptions::JSON => array_merge([
                'login' => $login,
                'password' => $password,
                'pledge_vendor_code' => $pledgeVendorCode,
                'delivery_vendor_code' => $delivery,
                'default_vendor_code' => $defaultVendorCode,
                'url' => $url,
            ], $data)
        ]);

        $body = json_decode($response->getBody()->getContents(), true);
        if ($response->getStatusCode() === 200) {
            if (!empty($body['uuid'])) {
                $this->companyBranch->OneCConnection()->save(new Connector([
                    'onec_id' => $body['uuid']
                ]));
            }
        }

        return response()->json($body, $response->getStatusCode());
    }


    function addInvoice($invoiceNumber, $invoiceId, $clientInn, $contractorInn, $data, $useOneCNaming = false)
    {

        $response = $this->client->post('invoice', [
            RequestOptions::JSON => [
                'invoice_number' => $invoiceNumber,
                'invoice_id' => $invoiceId,
                'client_inn' => $clientInn,
                'contractor_inn' => $contractorInn,
                'items' => $data,

            ]
        ]);
        $message = json_decode($response->getBody()->getContents(), true);
        logger()->info('addInvoice', [
            'body' => $response->getBody(),
            'content' => $response->getBody()->getContents(),
            'data' => $message
        ]);
        return [
            'message' => $response->getStatusCode() !== 200 ? $message['message'] ?? multi_implode($message, PHP_EOL) : $message,
            'code' => $response->getStatusCode()
        ];
    }

    function addRelease($uuid, $items)
    {
        $response = $this->client->post('release', [
            RequestOptions::JSON => [
                'invoice_uuid' => $uuid,
                'items' => $items,
            ]
        ]);
        $message = json_decode($response->getBody()->getContents(), true);

        return [
            'message' => $response->getStatusCode() !== 200 ? $message['message'] ?? multi_implode($message, PHP_EOL) : $message,
            'code' => $response->getStatusCode()
        ];
    }

    function checkConnection()
    {
        $response = $this->client->post('check', [
            RequestOptions::JSON => []
        ]);

        return [
            'data' => json_decode($response->getBody()->getContents(), true),
            'code' => $response->getStatusCode()
        ];
    }

    function markDelete($id)
    {
        $response = $this->client->post('mark-delete', [
            RequestOptions::JSON => [
                'filter' => 'Document_СчетНаОплатуПокупателю',
                'invoice_id' => $id,
            ]
        ]);
    }

    function getContractNumber($inn)
    {
        $response = $this->client->get("contract/{$inn}");
        $data = json_decode($response->getBody()->getContents(), true);
        if($data) {
            $data['Date'] = Carbon::parse($data['Дата'])->format('Y-m-d');
        }
        return [
            'data' => $data ?: null,
            'code' => $response->getStatusCode()
        ];
    }


    function getEntityInfo(string $entity, $filter)
    {

        switch ($entity) {
            case Machinery::class:
            case 'delivery':
                $response = $this->client->get('search', [
                    RequestOptions::JSON => [
                        'filter' => 'Catalog_Номенклатура',
                        'vendor_code' => $filter,
                    ]
                ]);
                break;
            case DispatcherInvoice::class:
                $response = $this->client->get('search', [
                    RequestOptions::JSON => [
                        'filter' => 'Document_СчетНаОплатуПокупателю',
                        'invoice_id' => $filter,
                    ]
                ]);
                break;
            case Contract::class:
                break;
            case Customer::class:
                $response = $this->client->get('search', [
                    RequestOptions::JSON => [
                        'filter' => 'Catalog_Контрагенты',
                        'inn' => $filter,
                    ]
                ]);
                break;
            case Stock::class:
                $response = $this->client->get('search', [
                    RequestOptions::JSON => [
                        'filter' => 'Catalog_Склады',
                    ]
                ]);
                break;
        }

        return json_decode($response->getBody()->getContents(), true);
    }

    function checkClient($data, $contract = [])
    {
        $data = array_merge($data, ['contract' => $contract]);
        $this->client->post('check-client', [
            RequestOptions::JSON => $data
        ])->getBody()->getContents();
    }

    function partDocument($data)
    {
        $response = $this->client->post('part-document', [
            RequestOptions::JSON => $data
        ]);
        $message = json_decode($response->getBody()->getContents(), true);

        return [
            'message' => $response->getStatusCode() !== 200 ? $message['message'] ?? multi_implode($message, PHP_EOL) : $message,
            'code' => $response->getStatusCode()
        ];
    }
}
