<?php

namespace Modules\Integrations\Entities;

use Carbon\Carbon;
use GuzzleHttp\RequestOptions;
use Illuminate\Database\Eloquent\Model;
use Modules\CompanyOffice\Services\BelongsToCompanyBranch;
use Modules\Integrations\Entities\Telpehony\TelephonyCallHistory;

class MangoTelephony extends Model
{

    use BelongsToCompanyBranch;

    protected $table = 'telephony_mango';

    protected $fillable = [
        'token',
        'sign',
        'company_branch_id'
    ];

    protected $casts = [
        'settings' => 'object'
    ];

    static $types = [
        1 => 'in',
        2 => 'out',
        3 => 'inner',
    ];

    static $statuses = [
        1 => 'Success',
        0 => 'NotAvailable',
        //'Не отвечен' => 'NotAvailable',
    ];

    function getClient()
    {
        $client = new \GuzzleHttp\Client([
            'base_uri' => 'https://app.mango-office.ru/',
        ]);

        return $client;
    }

    function sendRequest($path, array $data)
    {
        $client = $this->getClient();

        $apiKey = $this->token;
        $salt = $this->sign;
        $json = json_encode($data);
        $sign = hash('sha256', $apiKey . $json . $salt);

        $response = $client->post($path, [
            'http_errors' => false,
            RequestOptions::FORM_PARAMS => [
                'vpbx_api_key' => $apiKey,
                'sign' => $sign,
                'json' => $json
            ]
        ]);

        return [
            $response->getStatusCode(),
            $response->getBody()->getContents()
        ];
    }

    function getStats(Carbon $dateFrom, Carbon $dateTo)
    {

        [$code, $content] = $this->sendRequest('vpbx/stats/calls/request', [
            'start_date' => $dateFrom->format('d.m.Y H:i:s'),
            'end_date' => $dateTo->format('d.m.Y H:i:s'),
            "limit" => "100",
            "offset" => "0"
        ]);

        $key = json_decode($content, true)['key'];

        return $this->getResultByKey($key);
    }

    function getResultByKey($key)
    {
        [$code, $content] = $this->sendRequest('vpbx/stats/calls/result', [
            'key' => $key,
        ]);
        $result = json_decode($content, true);

        if ($code === 200 && !empty($result['key']) && $result['status'] === 'work') {
            sleep(3);
            return $this->getResultByKey($result['key']);
        }

        return $result;
    }

    function calls_history()
    {
        return $this->morphMany(TelephonyCallHistory::class, 'owner');
    }


    function parseCall($data)
    {
        $existsCall = $this->calls_history()->where('call_id', $data['entry_id'])->first();
        $data['type'] = static::$types[$data['context_type']];

        $data['diversion'] =  $data['type'] === 'in' ? $data['called_number'] : $data['caller_number'];

        $fields = [
            'phone' =>  $data['type'] === 'in' ? trimPhone($data['caller_number']) : trimPhone($data['called_number']),
            'call_id' => $data['entry_id'],
            'raw_data' => $data,
        ];

        $fields['status'] = static::$statuses[$data['context_status']];

        $fields['manager_phone'] = $data['diversion'];
        $dt = Carbon::createFromTimestamp( $data['context_start_time']);
        if ($existsCall) {
            $existsCall->timestamps = false;
            $existsCall->created_at = $dt;
            $existsCall->updated_at = $dt;
            $existsCall->fill($fields);
            $existsCall->save();
        } else {
            $call = new TelephonyCallHistory($fields);
            $call->company_id = $this->company_branch->company_id;

            $call->timestamps = false;
            $call->created_at = $dt;
            $call->updated_at = $dt;

            $existsCall = $this->calls_history()->save($call);
        }
        return $existsCall;
    }

}
