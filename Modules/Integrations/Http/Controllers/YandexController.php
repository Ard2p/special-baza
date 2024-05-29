<?php

namespace Modules\Integrations\Http\Controllers;

use App\Service\RequestBranch;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Modules\CompanyOffice\Entities\Company\CompanyBranch;

class YandexController extends Controller
{

    /** @var CompanyBranch */
    private $currentBranch;

    public function __construct(Request $request, RequestBranch $companyBranch)
    {
        $this->currentBranch = $companyBranch->companyBranch;
    }

    /**
     * Display a listing of the resource.
     * @return Response
     */
    public function index()
    {
        $settings = $this->currentBranch->getSettings();

        return response()->json(['ya_disk_oauth' => $settings->ya_disk_oauth]);
    }


    /**
     * Store a newly created resource in storage.
     * @param Request $request
     * @return Response
     */
    public function store(Request $request)
    {
        $settings = $this->currentBranch->getSettings();
        $settings->update([
            'ya_disk_oauth' => $request->input('ya_disk_oauth')
        ]);

        return response()->json();
    }

}
