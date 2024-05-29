<?php

namespace Modules\Integrations\Entities\Uis;

use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\RequestOptions;
use Illuminate\Database\Eloquent\Model;
use Modules\CompanyOffice\Services\BelongsToCompany;
use Modules\Integrations\Entities\Telpehony\TelephonyCallHistory;

class UisTelephony extends Model
{

    use BelongsToCompany;

    protected $fillable = ['token'];

    protected $table = 'telephony_uis_accounts';

    private $baseUrl = 'https://dataapi.comagic.ru/v2.0';

    function calls_history()
    {
        return $this->morphMany(TelephonyCallHistory::class, 'owner');
    }

    function getCalls()
    {
        $client = new Client(
            [
                'base_uri' => $this->baseUrl,

            ]
        );

        $response = $client->post('', [
            RequestOptions::JSON => [
                'id' => "12345",
                "jsonrpc" => "2.0",
                "method" => "get.calls_report",

                "params" => [
                    'date_from' => now()->setTimezone(config('app.timezone'))->subMinutes(20)->format('Y-m-d h:m:s'),
                    'date_till' => now()->endOfDay()->format('Y-m-d h:m:s'),
                    "access_token" => $this->token,

                ]
            ]
        ]);

        $data = json_decode($response->getBody()->getContents(), true);

        //logger(json_encode($data));
        if (!empty($data['result']['data'])) {
            \DB::beginTransaction();

            foreach ($data['result']['data'] as $call) {
                $this->parseCall($call);
            }

            \DB::commit();
        }
    }

    function parseCall($data)
    {
        $existsCall = $this->calls_history()->where('call_id', $data['id'])->first();
        $data['type'] = $data['direction'];
        $data['diversion'] = $data['virtual_phone_number'];
        $fields = [
            'phone' => $data['contact_phone_number'],
            'call_id' => $data['id'],
            'raw_data' => $data,
        ];
        if (!empty($data['call_records']) && !empty($data['call_records'][0])) {
            $fields['link'] = "https://app.comagic.ru/system/media/talk/{$data['communication_id']}/{$data['call_records'][0]}/";
        }
        if (!empty($data['finish_reason'])) {
            $fields['status'] = 'Success';

            switch ($data['finish_reason']) {
                case 'operator_disconnects':
                    if ($data['is_lost'])
                        $fields['status'] = 'NotAvailable';
                    break;
                case 'subscriber_busy':
                case 'subscriber_not_responsible':
                case 'operator_not_responsible':
                case 'operator_busy':
                    $fields['status'] = 'Busy';
                    break;

            }

        }else{
            $fields['status'] = 'Success';
        }

        $fields['manager_phone'] = trimPhone($data['virtual_phone_number']);

        if ($existsCall) {
            $existsCall->update($fields);
        } else {
            $call = new TelephonyCallHistory($fields);
            $call->company_id = $this->company_id;

            $call->timestamps = false;
            $call->created_at = $data['start_time'];
            $call->updated_at = $data['start_time'];

            $existsCall = $this->calls_history()->save($call);
        }
        return $existsCall;
    }
}
