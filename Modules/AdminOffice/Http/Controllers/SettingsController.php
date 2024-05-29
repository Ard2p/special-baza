<?php

namespace Modules\AdminOffice\Http\Controllers;

use App\Option;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Validator;

class SettingsController extends Controller
{
    function getCommission()
    {
        $options = config('global_options');


        return response()->json([
            'system_commission' => $options->where('key', 'system_commission')->first()->value / 100,
            'representative_commission' => $options->where('key', 'representative_commission')->first()->value / 100,
            'widget_commission' => $options->where('key', 'widget_commission')->first()->value / 100,
        ]);
    }

    function setCommission(Request $request)
    {
        $errors = Validator::make($request->all(), [

            'system_commission' => 'required|integer|min:0|max:99',
            'representative_commission' => 'required|integer|min:0|max:99',
            'widget_commission' => 'required|integer|min:0|max:99',

        ])->errors()->getMessages();

        if ($errors) {
            return response()->json($errors, 400);
        }

        Option::find('system_commission')->update(
            [
                'value' => round((int)$request->input('system_commission', 0) * 100)
            ]);
        Option::find('representative_commission')->update(
            [
                'value' => round((int)$request->input('representative_commission', 0) * 100)
            ]);
        Option::find('widget_commission')->update(
            [
                'value' => round((int)$request->input('widget_commission', 0) * 100)
            ]);

        return response('OK');

    }

    function getGeneral()
    {
        $options = config('global_options');


        return response()->json([
            'analytics_head' => $options->where('key', 'analytics_head')->first()->value,
            'analytics_body' => $options->where('key', 'analytics_body')->first()->value,
        ]);
    }

    function setGeneral(Request $request)
    {
        $errors = Validator::make($request->all(), [

            'analytics_head' => 'required|string',
            'analytics_body' => 'required|string',

        ])->errors()->getMessages();

        if ($errors) {
            return response()->json($errors, 400);
        }

        Option::find('analytics_head')->update(
            [
                'value' => $request->input('analytics_head')
            ]);
        Option::find('analytics_body')->update(
            [
                'value' => $request->input('analytics_body')
            ]);


        return response('OK');

    }

    function getCryptName(Request $request)
    {
     return response()->json([
          'file' => Crypt::encrypt($request->input('crypt_file')),
          'folder' => Crypt::encrypt($request->input('crypt_folder')),
     ]);
    }
}
