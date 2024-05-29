<?php

namespace Modules\Integrations\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Modules\CompanyOffice\Entities\Company\CompanyBranch;
use Modules\ContractorOffice\Transformers\Vehicle;

class AppraiserController extends Controller
{


    function getMachineries($branchId)
    {
        /** @var CompanyBranch $branch */
        $branch = CompanyBranch::query()->findOrFail($branchId);

        return Vehicle::collection($branch->machines()->whereReadOnly(false)->get());
    }
}
 