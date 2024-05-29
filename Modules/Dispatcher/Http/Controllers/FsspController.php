<?php

namespace Modules\Dispatcher\Http\Controllers;

use GuzzleHttp\Client;
use GuzzleHttp\RequestOptions;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;

class FsspController extends Controller
{

    private  $client;
    public function __construct()
    {
        $this->client = new Client([
            'base_uri' => 'https://api-ip.fssp.gov.ru/api/v1.0/',
            'http_errors' => false
        ]);
    }

    function search(Request $request)
    {
        $search = $this->client->post('search/group', [
            RequestOptions::JSON => $request->all()
        ]);

        return response()->json(
            json_decode($search->getBody()->getContents(), true),
            $search->getStatusCode()
        );
    }

    function result(Request $request)
    {
        $search = $this->client->get('result', [
            RequestOptions::JSON => $request->all()
        ]);

        return response()->json(
            json_decode($search->getBody()->getContents(), true),
            $search->getStatusCode()
        );
    }
}
