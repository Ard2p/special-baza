<?php

namespace Modules\AdminOffice\Http\Controllers;

use App\Helpers\RequestHelper;
use App\Machinery;
use App\Option;
use App\Service\Insurance\InsuranceService;
use App\User;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Modules\AdminOffice\Entities\Filter;
use Modules\AdminOffice\Entities\RpContact;
use Modules\AdminOffice\Entities\SiteFeedback;
use Modules\CompanyOffice\Entities\Company\InsCertificate;

class InsuranceController extends Controller
{

    function get(Request $request, $id = null)
    {
        $certificates = InsCertificate::query();

        $filter = new Filter($certificates);
        $filter->getLike([
            'number' => 'number',
        ]);

        return $certificates->paginate($request->per_page ?: 10);
    }

    function export(Request $request)
    {
        $insService = new InsuranceService();
        $path = $insService->export($request->input('date_from'), $request->input('date_to'));

        return response()->json([
            'url' => Storage::disk('public')->url($path)
        ]);
    }

    function exportBranchCertificates(Request $request)
    {
        $insService = new InsuranceService();
        $path = $insService->export($request->input('date_from'), $request->input('date_to'), request_branch());

        return response()->json([
            'url' => Storage::disk('public')->url($path)
        ]);
    }
}
