<?php

namespace Modules\CompanyOffice\Http\Controllers\Insurance;

use App\Service\RequestBranch;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\CompanyOffice\Entities\Company\InsTariff;

class InsTariffController extends Controller
{
    public function show(Request $request, InsTariff $ins_tariff)
    {
        return $ins_tariff;
    }
}
