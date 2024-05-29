<?php

namespace Modules\CompanyOffice\Http\Controllers;

use App\Service\RequestBranch;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Modules\CompanyOffice\Entities\Company\CompanyBranch;

class CompanyDocumentsController extends Controller
{
    private CompanyBranch $currentBranch;

    public function __construct(RequestBranch $requestBranch)
    {
        $this->currentBranch = $requestBranch->companyBranch;
    }

    /**
     * Display a listing of the resource.
     * @return Response
     */
    public function index()
    {
        $this->currentBranch->loadMissing('documents.user');
        return $this->currentBranch->documents;
    }


    /**
     * Store a newly created resource in storage.
     * @param Request $request
     * @return Response
     */
    public function store(Request $request)
    {
       $request->validate([
         'name' => 'required|string',
         'url' => 'required|string',
         'description' => 'required|string',
       ]);

       $this->currentBranch->addDocument(
           $request->input('name'),
           $request->input('url'),
           details: [
               'description' => $request->input('description')
           ],
       );
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'name' => 'required|string',
            'url' => 'required|string',
            'description' => 'required|string',
        ]);

        $document = $this->currentBranch->documents()->findOrFail($id);
        DB::beginTransaction();
        $this->currentBranch->updateDocument($document,
            $request->input('name'),
            $request->input('url'),
            details: [
                'description' => $request->input('description')
            ],
        );

        DB::commit();
    }

    public function destroy($id)
    {
        $this->currentBranch->documents()->findOrFail($id)->delete();
    }
}
