<?php

namespace Modules\Integrations\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class IntegrationsController extends Controller
{
    /**
     * Display a listing of the resource.
     * @return Response
     */
    public function index()
    {
        $api = Auth::user()->adminIntegation;

        return view('integrations::index', compact('api'));
    }



    function update(Request $request)
    {
        $errors = Validator::make($request->all(), ['url' => 'required|url'])->errors()->getMessages();

        if($errors) {

            return redirect()->back();
        }

        $api = Auth::user()->adminIntegation;

        $api->update([
            'event_back_url' => $request->input('url')
        ]);

        return redirect()->back();
    }

    function docs()
    {
        return view('integrations::docs');
    }


    function proxyDocs()
    {
        return view('integrations::proxy');
    }

}
