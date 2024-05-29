<?php

namespace Modules\CompanyOffice\Http\Controllers;

use App\Service\RequestBranch;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Modules\CompanyOffice\Entities\Company\CompanyBranch;
use Modules\CompanyOffice\Entities\Company\DocumentsPack;
use Modules\CompanyOffice\Services\CompanyRoles;

class DocumentsPacksController extends Controller
{
    /** @var CompanyBranch */
    private $currentBranch;

    public function __construct(Request $request, RequestBranch $companyBranch)
    {
        $this->currentBranch = $companyBranch->companyBranch;

        $block = $this->currentBranch->getBlockName(CompanyRoles::BRANCH_DASHBOARD);
        $this->middleware("accessCheck:{$block}," . CompanyRoles::ACTION_SHOW)->only(
            [
                'index',
                'show',
            ]);
        $this->middleware("accessCheck:{$block}," . CompanyRoles::ACTION_UPDATE)->only(
            [
                'store',
                'update',
                'destroy',

            ]);

    }

    /**
     * Display a listing of the resource.
     * @return Response
     */
    public function index()
    {
        return $this->currentBranch->documentsPack;
    }


    /**
     * Store a newly created resource in storage.
     * @param Request $request
     * @return Response
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'type_from' => 'required|in:' . implode(',', DocumentsPack::AVAILABLE_TYPES),
            'type_to' => 'required|in:' . implode(',', DocumentsPack::AVAILABLE_TYPES),
        ]);
        $doc = new DocumentsPack();

        $doc->company_branch_id = $this->currentBranch->id;

        \DB::beginTransaction();
        $doc->setDocuments($request->all());
        \DB::commit();

        return response()->json();
    }

    /**
     * Show the specified resource.
     * @param int $id
     * @return Response
     */
    public function show($branch, $id)
    {
        return DocumentsPack::query()->forBranch()->findOrFail($id);
    }


    /**
     * Update the specified resource in storage.
     * @param Request $request
     * @param int $id
     * @return Response
     */
    public function update(Request $request,$branch, $id)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'type_from' => 'required|in:' . implode(',', DocumentsPack::AVAILABLE_TYPES),
            'type_to' => 'required|in:' . implode(',', DocumentsPack::AVAILABLE_TYPES),
        ]);

        /** @var DocumentsPack $doc */
        $doc = DocumentsPack::query()->forBranch()->findOrFail($id);

        \DB::beginTransaction();
        $doc->setDocuments($request->all());
        \DB::commit();

        return response()->json();
    }

    /**
     * Remove the specified resource from storage.
     * @param int $id
     * @return Response
     */
    public function destroy($branch, $id)
    {
        $doc = DocumentsPack::query()->forBranch()->findOrFail($id);

        $doc->delete();
    }

    public function saveTemplate(Request $request, $branch, $id)
    {
        $doc = DocumentsPack::query()->forBranch()->findOrFail($id);
        $doc->update([
            $request->input('type') => $request->input('data')
        ]);
    }
}
