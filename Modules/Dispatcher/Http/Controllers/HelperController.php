<?php

namespace Modules\Dispatcher\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Jurosh\PDFMerge\PDFMerger;
use Lang;

class HelperController extends Controller
{


    public function rejectReasons(Request $request)
    {
        $statuses = collect(Lang::get('transbaza_statuses'));
        if($request->filled('avito')){
            $statuses = $statuses->filter(function ($i, $k) {
                return str_contains($i, 'Отказ сделки Авито');
            });
            $statuses = $statuses->filter(function ($i, $k) {
                return $i !== 'Отказ сделки Авито';
            });
        }else {
            $statuses = $statuses->filter(function ($i, $k) {
                return str_contains($k, 'proposal_reject_');
            });
        }
        $data = [];
        foreach ($statuses as $k => $status) {
            $data[str_replace('proposal_reject_', '', $k)] = $status;
        }


        return $data;
    }
}
