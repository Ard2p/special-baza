<?php

namespace Modules\CompanyOffice\Http\Controllers\Directories;

use App\Service\RequestBranch;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Illuminate\Support\Str;
use Modules\CompanyOffice\Entities\Company\CompanyBranch;
use Modules\CompanyOffice\Entities\Directories\SlangCategory;
use Modules\CompanyOffice\Services\CompanyRoles;

class SlangCategoryController extends Controller
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
                'store',
                'inviteInfo',
                'updateBranch',
                'getEmployees',
            ]);
    }

    /**
     * Display a listing of the resource.
     * @return Response
     */
    public function index()
    {
        return $this->currentBranch->company->slangCategories->sortBy('name')->values()->all();
    }


    /**
     * Store a newly created resource in storage.
     * @param Request $request
     * @return Response
     */
    public function store(Request $request)
    {
        $request->validate([
            '*.category_id'        => 'required|exists:types,id',
            '*.brand_id'           => 'nullable|exists:brands,id',
            '*.model_id'           => 'nullable|exists:machinery_models,id',
            '*.name'               => 'required|string',
            '*.insurance_premium'  => 'required|numeric|min:0',
            '*.rent_days_count'    => 'required|numeric|min:0',
            '*.service_days_count' => 'required|numeric|min:0',
        ]);

        foreach ($request->all() as $item) {
            $fields = [
                'category_id'        => $item['category_id'],
                'brand_id'           => $item['brand_id'] ?? null,
                'model_id'           => $item['model_id'] ?? null,
                'name'               => $item['name'],
                'insurance_premium'  => $item['insurance_premium'],
                'rent_days_count'    => $item['rent_days_count'],
                'service_days_count' => $item['service_days_count'],
            ];
            if (!empty($item['id'])) {
                $current = $this->currentBranch->company->slangCategories()->findOrFail($item['id']);
                $current->update($fields);
            } else {
                $this->currentBranch->company->slangCategories()->save(new SlangCategory($fields));
            }
        }

    }

    /**
     * Remove the specified resource from storage.
     * @param int $id
     * @return Response
     */
    public function destroy($id)
    {
        logger($id);
        $cat = $this->currentBranch->company->slangCategories()->findOrFail($id);
        $cat->delete();
    }
}
