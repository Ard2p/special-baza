<?php

namespace Modules\FMSAPI\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Validator;
use Modules\FMSAPI\Entities\FmsApi;
use Modules\Integrations\Entities\Integration;

class FMSAPIController extends Controller
{
    /**
     * Display a listing of the resource.
     * @return Response
     */


    function rest(Request $request)
    {
        $data = $request->json()->all();
        $errors = Validator::make($data, FmsApi::getValidatorRules())->errors()->getMessages();
        if($errors){
            return response()->json($errors, 419);
        }

        $api = new FmsApi();

        $result = $api->processData($data);

        return response()->json($result);
    }

}
