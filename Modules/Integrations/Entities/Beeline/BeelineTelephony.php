<?php


namespace Modules\Integrations\Entities\Beeline;


use GuzzleHttp\Client;
use GuzzleHttp\RequestOptions;
use Illuminate\Database\Eloquent\Model;
use Modules\CompanyOffice\Services\BelongsToCompany;
use Modules\Integrations\Entities\Telpehony\TelephonyCallHistory;

class BeelineTelephony extends Model
{

    use BelongsToCompany;

    protected $table = 'telephony_beeline_accounts';

    protected $fillable = ['api_token', 'url_token'];

    //  private $apiKey = 'c982ce25-fedf-4822-ad3c-cf4dc3056809';
    private $baseUrl = 'https://cloudpbx.beeline.ru/apis/portal/';
    private $client;

    const XSI_EVENT_ID = 'eventData.call.callId';

    const XSI_MANAGER_PHONE_ENDPOINT = 'targetId';

    const XSI_DIRECTION = 'eventData.call.personality';
    const XSI_EVENT_TYPE = 'eventData.@attributes.type';


    const XSI_CALLER_PHONE = 'eventData.call.remoteParty.address';
    const XSI_ANSWER_TIME = 'eventData.answerTime';

    static $types = [
        'CallReceivedEvent',
        'CallAnsweredEvent'
    ];

    function getRecords()
    {
        $client =  new Client(
            [
                'base_uri' => $this->baseUrl,
                'headers' => [
                    'X-MPBX-API-AUTH-TOKEN' => $this->api_token,
                    'Content-Type' => 'application/json',
                ],
            ]
        );

        $request = $client->get('/statistics ', [
            'http_errors' => false,
            RequestOptions::QUERY => [
                'dateFrom' => (string) now()->subDays(2)->utc()->toIso8601String(),
                'dateTo' => (string) now()->utc()->toIso8601String(),
            ],
        ]);
        $val = $request->getBody()->getContents();

        dd($val);
    }


    function calls_history()
    {
        return $this->morphMany(TelephonyCallHistory::class, 'owner');
    }

    function getManagerPhone($data)
    {

        return trimPhone(data_get($data, self::XSI_MANAGER_PHONE_ENDPOINT, ''));
    }

    function getCallerPhone($data)
    {
        return trimPhone(data_get($data, self::XSI_CALLER_PHONE, ''));
    }

    function getEventType($data)
    {
        return data_get($data, self::XSI_EVENT_TYPE, null);
    }

    function hasAnswerTime($data)
    {
        return data_get($data, self::XSI_ANSWER_TIME, null);
    }


    function getEventId($data)
    {
        return data_get($data, self::XSI_EVENT_ID, null);
    }

    function getDirection($data)
    {
        return in_array($this->getEventType($data), self::$types) ? 'incoming' : 'outgoing';
    }


    function registerSubscription()
    {

       $client =  new Client(
            [
                'base_uri' => $this->baseUrl,
                'headers' => [
                    'X-MPBX-API-AUTH-TOKEN' => $this->api_token,
                    'Content-Type' => 'application/json',
                ],
            ]
        );
        $request = $client->put('subscription', [
            'http_errors' => false,
            RequestOptions::JSON => [
                'subscriptionType' => 'BASIC_CALL',
                'expires' => 86400,
                'url' => 'https://api.trans-baza.ru/v1/beeline/postback/' . $this->url_token,
            ],
        ]);
        $val = $request->getBody()->getContents();


        //logger($val);
    }


    function parseCall($data)
    {
        $managerPhone = $this->getManagerPhone($data);
        $data['id'] = $this->getEventId($data);
        $type = $this->getEventType($data);
        $callerPhone = $this->getCallerPhone($data);
        $callerPhone = $callerPhone ? $callerPhone : null;
        $managerPhone = $managerPhone ? '7' . $managerPhone : null;

        //\Log::info($managerPhone . " " . $type . ' ' . $data['id'] . ' ' . $callerPhone . $this->getDirection($data));

        if (!$managerPhone || !$data['id'] || !$type)
            return;

        $existsCall = $this->calls_history()->where('call_id', $data['id'])->first();

        $data['diversion'] = $managerPhone;
        $fields = [
            'phone' => $callerPhone,
            'call_id' => $data['id'],
            'raw_data' => $data,
        ];
        /* if (!empty($data['call_records']) && !empty($data['call_records'][0])) {
             $fields['link'] = "https://app.comagic.ru/system/media/talk/{$data['communication_id']}/{$data['call_records'][0]}/";
         }*/

        $data['type'] = $this->getDirection($data);


        switch ($type) {
            case 'CallReleasedEvent':
                $fields['status'] = 'NotAvailable';
                break;
            default:
                $fields['status'] = 'NotAvailable';

        }
        if ($this->hasAnswerTime($data)) {
            $fields['status'] = 'Success';
        }


        $fields['manager_phone'] = $managerPhone;

        if ($existsCall) {
            unset($fields['type']);
            $existsCall->update($fields);
        } else {
            $call = new TelephonyCallHistory($fields);
            $call->company_id = $this->company_id;

            $existsCall = $this->calls_history()->save($call);
        }
        return $existsCall;
    }
}


