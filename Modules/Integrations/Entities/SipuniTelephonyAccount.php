<?php

namespace Modules\Integrations\Entities;

use Carbon\Carbon;
use GuzzleHttp\Client;
use GuzzleHttp\RequestOptions;
use Illuminate\Database\Eloquent\Model;
use Modules\CompanyOffice\Services\BelongsToCompanyBranch;
use Modules\Integrations\Entities\Telpehony\TelephonyCallHistory;

class SipuniTelephonyAccount extends Model
{
    use BelongsToCompanyBranch;

    protected $fillable = [
        'token',
        'company_branch_id',
        'settings'
    ];

    protected $casts = [
        'settings' => 'object'
    ];
    static $statuses = [
         'Отвечен' => 'Success',
         'Не отвечен' => 'NotAvailable',
         //'Не отвечен' => 'NotAvailable',
    ];

    static $types = [
        'Входящий' => 'in',
        'Исходящий' => 'out',
        'Внутренний' => 'inner',
    ];
    private $url = 'https://sipuni.com/api/';

    function calls_history()
    {
        return $this->morphMany(TelephonyCallHistory::class, 'owner');
    }

    public function getStats(
        Carbon $dateFrom,
        Carbon $dateTo)
    {
        $user = '034511';
        $from = $dateFrom->format('d.m.Y');
        $to = $dateTo->format('d.m.Y');
        $type = '0';
        $state = '0';
        $timeFrom = $dateFrom->format('H:i');
        $timeTo = $dateTo->format('H:i');
        $tree = '';
        //     $rating = '5';
        $showTreeId = '1';
        $fromNumber = '';
        $numbersRinged = 0;
        $numbersInvolved = 0;
        $names = 0;
        $outgoingLine = 1;
        $toNumber = '';
        $toAnswer = '';
        $anonymous = '1';
        $firstTime = '0';
        $dtmfUserAnswer = 0;
        $secret = $this->token;
        $hashString =
            join('+', [$anonymous, $dtmfUserAnswer, $firstTime, $from, $fromNumber, $names, $numbersInvolved, $numbersRinged, $outgoingLine, $showTreeId, $state, $timeFrom, $timeTo, $to, $toAnswer, $toNumber, $tree, $type, $user, $secret]);
        $hash = md5($hashString);
        $client = new Client(
            [
                'base_uri'       => $this->url,
                'http_errors'    => false,
                'decode_content' => false
            ]
        );

        $request = $client->get('statistic/export', [
            RequestOptions::QUERY => [
                'anonymous'       => $anonymous,
                'dtmfUserAnswer'  => $dtmfUserAnswer,
                'firstTime'       => $firstTime,
                'from'            => $from,
                'fromNumber'      => $fromNumber,
                'names'           => $names,
                'numbersInvolved' => $numbersInvolved,
                'numbersRinged'   => $numbersRinged,
                'outgoingLine'    => $outgoingLine,
                //   'rating' => $rating,
                'showTreeId'      => $showTreeId,
                'state'           => $state,
                'timeFrom'        => $timeFrom,
                'timeTo'          => $timeTo,
                'to'              => $to,
                'toAnswer'        => $toAnswer,
                'toNumber'        => $toNumber,
                'tree'            => $tree,
                'type'            => $type,
                'user'            => $user,
                'hash'            => $hash,
            ]
        ]);
        $results = collect(array_map(function ($item) {
            return str_getcsv($item, ';');
        }, explode("\n", $request->getBody()->getContents())))->take(100);

        $results->shift();
     //   dd($results->shift(), $results->shift());
        \DB::beginTransaction();

        foreach ($results as $result) {
            $this->parseCall($result);
        }

        \DB::commit();
    }

    function parseCall($data)
    {
        if(empty($data['13'])) {
            return;
        }
        $existsCall = $this->calls_history()->where('call_id', $data[13])->first();
        $data['type'] = static::$types[$data[0]];

        $data['diversion'] =  $data['type'] === 'in' ? trimPhone($data[7]) : trimPhone($data[6]);

        $fields = [
            'phone' =>  $data['type'] === 'in' ? trimPhone($data[6]) : trimPhone($data[7]),
            'call_id' => $data[13],
            'raw_data' => $data,
        ];

        $fields['status'] = static::$statuses[$data[1]];

        if($fields['status'] === 'Success') {

            $user = '034511';
            $id = $data[13];
            $secret =  $this->token;

            $hashString = join('+', array($id, $user, $secret));
            $hash = md5($hashString);

            $url = 'https://sipuni.com/api/statistic/record';
            $query = http_build_query(array(
                'id' => $id,
                'user' => $user,
                'hash' => $hash,
            ));
            $fields['link'] = $url.'?'. $query;
        }

        $fields['manager_phone'] = $data['diversion'];
        $dt = Carbon::createFromFormat('d.m.Y H:i:s', $data[2]);
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
