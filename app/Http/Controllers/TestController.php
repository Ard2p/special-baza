<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Avito\Dto\CreateOrderConditions;
use App\AppSetting;
use App\Http\Controllers\Avito\Models\AvitoOrder;
use App\Http\Controllers\Avito\Models\AvitoOrderHistory;
use App\Http\Controllers\Avito\Models\AvitoStat;
use App\Http\Controllers\Avito\Repositories\AvitoRepository;
use App\Jobs\CreateAvitoOrderJob;
use App\Machinery;
use App\Service\AlfaBank\AlfaBankInvoiceService;
use App\Service\Avito\AvitoApiService;
use App\Service\DaData;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use DB;
use Storage;

class TestController extends Controller
{


    public function index()
    {
        //throw new Exception('Exception test!!!!!');
    }

    public function hook(Request $request)
    {
        \Log::debug('Hook', $request->all());
        return response()->json(['status' => 'ok']);
    }
}
