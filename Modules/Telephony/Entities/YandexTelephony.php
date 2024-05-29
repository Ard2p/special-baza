<?php

namespace Modules\Telephony\Entities;

use GuzzleHttp\Client;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Modules\Telephony\Events\CallsListen;

class YandexTelephony
{
    private $apikey = '86b8fa62-f0e4-4607-9da8-9a30661c138b';
    private $requestApiKey = '';
    private $currentNumbers = [
        '+74959757528',
    ];
    private $incommingCall = false;

    const CALL_START = 'IncomingCall';
    const CALL_IN_PROGRESS = 'IncomingCallConnected';
    const CALL_COMPLETE = 'IncomingCallCompleted';

    const OUTGOING_CALL_START = 'OutgoingCall';
    const OUTGOING_CALL_IN_PROGRESS = 'OutgoingCallConnected';
    const OUTGOING_CALL_COMPLETE = 'OutgoingCallCompleted';


    private $phone_from;
    private $phone_to;
    private $event;
    private $call_id;

    public function __construct($data)
    {

        $this->phone_from = trimPhone($data['Body']['From']);
        $this->phone_to = trimPhone($data['Body']['To']);

        $this->incommingCall =  in_array($data['Body']['To'], $this->currentNumbers);

        $this->event = $data['EventType'];

        $this->requestApiKey = $data['ApiKey'];

        $this->call_id = $data['CallId'];

        $this->pushCallState();
    }


    private function pushCallState()
    {
        DB::beginTransaction();
        $call = Call::whereCallId($this->call_id)->with('user')->first();

        if ($call) {
            $update = [
                'call_status' => $this->event
            ];

            if ($this->event === self::CALL_IN_PROGRESS || $this->event === self::OUTGOING_CALL_IN_PROGRESS) {
                $update['answered'] = true;
            }

            if ($this->event === self::CALL_COMPLETE || $this->event === self::OUTGOING_CALL_COMPLETE) {
                $update['global_status'] = 'complete';
            }

            $call->update($update);


        } else {
            $call = Call::create([
                'phone' => $this->incommingCall ? $this->phone_from : $this->phone_to,
                'call_id' => $this->call_id,
                'call_status' => $this->event,
                'global_status' => 'start',
                'type' => $this->incommingCall ? 'incoming' : 'outgoing',
            ]);
        }


        CallsListen::dispatch($call);

        DB::commit();

    }


}
