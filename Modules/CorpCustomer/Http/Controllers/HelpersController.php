<?php

namespace Modules\CorpCustomer\Http\Controllers;

use App\Rules\Inn;
use App\Service\DaData;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;

class HelpersController extends Controller
{
    function daDataHelper(Request $request)
    {
        $request->validate([

            'inn' => ['nullable', new Inn()]
        ]);

        $data = new DaData();

        if ($request->filled('inn')) {
            $response = $data->searchByInn($request->input('inn'), $request->input('type', 'LEGAL'));
        }

        return response()->json($response ?? []);
    }

    function searchByBik(Request $request)
    {
        $request->validate([

            'bik' => 'required'
        ]);

        $data = new DaData();

        $response = $data->searchByBik($request->input('bik'));

        return response()->json($response ?? []);
    }

    function dadataAction(Request $request)
    {
        $request->validate([
            'action' => 'required|in:fms_unit,passport,name',
            'data' => 'required',
        ]);

        $cleaner = in_array($request->input('action'), ['passport', 'name']);

        $data = new DaData($request->input('data'), $cleaner);


        switch ($request->input('action')) {
            case 'passport':
                return response()->json($data->checkPassport());
            case 'name':
                return response()->json($data->cleanName());
            case 'fms_unit':
                return response()->json($data->getFmsUnit($request->input('data')));
        }
    }
}
