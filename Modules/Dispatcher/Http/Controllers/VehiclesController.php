<?php

namespace Modules\Dispatcher\Http\Controllers;

use App\Machines\Brand;
use App\Machines\Type;
use App\Service\RequestBranch;
use App\Support\Region;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Modules\AdminOffice\Entities\Filter;
use Modules\CompanyOffice\Services\CompanyRoles;
use Modules\Dispatcher\Entities\Directories\Contractor;
use Modules\Dispatcher\Entities\Directories\Vehicle;
use Modules\Dispatcher\Http\Requests\CreateVehicleRequest;
use Modules\RestApi\Entities\KnowledgeBase\Category;

class VehiclesController extends Controller
{

    private $currentBranch;

    public function __construct(Request $request, RequestBranch $companyBranch)
    {
        $this->currentBranch = $companyBranch->companyBranch;

        $block = $this->currentBranch->getBlockName(CompanyRoles::BRANCH_PROPOSALS);
        $this->middleware("accessCheck:{$block}," . CompanyRoles::ACTION_SHOW)->only(
            [
                'index',
                'show',
            ]);

        $this->middleware("accessCheck:{$block}," . CompanyRoles::ACTION_CREATE)->only(
            [
                'store',
                'update',
            ]);
    }

    /**
     * Display a listing of the resource.
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function index(Request $request)
    {
        $vehicles = Vehicle::query()->with('category', 'contractor', 'brand')->forBranch();

        $filter = new Filter($vehicles);

        $filter->getLike([
            'name' => 'name',
            'comment' => 'comment'
        ])->getEqual([
            'type_id' => 'type_id',
            'brand_id' => 'brand_id',
            'contractor_id' => 'contractor_id',
        ]);

        return $vehicles->paginate($request->per_page ?: 10);
    }


    /**
     * Store a newly created resource in storage.
     * @param Request $request
     * @return Response
     */
    public function store(CreateVehicleRequest $request)
    {
        $request->validated();

        $category = Category::query()->findOrFail($request->input('type_id'));

        $category->localization();

        $brand  =  Brand::query()->findOrFail($request->input('brand_id'));


       $vehicle = Vehicle::create([
            'name' => $request->input('name') ?: "{$category->name} {$brand->name}",
            'type_id' => $request->input('type_id'),
            'brand_id' => $request->input('brand_id'),
            'comment' => $request->input('comment'),
            'contractor_id' => $request->input('contractor_id')
        ]);

       return  response()->json($vehicle);
    }

    /**
     * Show the specified resource.
     * @param int $id
     * @return Response
     */
    public function show($id)
    {

        return Vehicle::forBranch()->findOrFail($id);
    }

    /**
     * Update the specified resource in storage.
     * @param Request $request
     * @param int $id
     * @return Response
     */
    public function update(CreateVehicleRequest $request, $id)
    {
        $request->validated();

        $vehicle = Vehicle::findOrFail($id);
        $vehicle->update([
            'name' => $request->input('name'),
            'type_id' => $request->input('type_id'),
            'brand_id' => $request->input('brand_id'),
            'comment' => $request->input('comment'),
            'contractor_id' => $request->input('contractor_id')
        ]);

        return  response()->json($vehicle);
    }

    /**
     * Remove the specified resource from storage.
     * @param int $id
     * @return Response
     */
    public function destroy($id)
    {
        //
    }



    function getFilters()
    {
        $categories = Type::query()->whereHas('machines', function ($q){
            $q->forBranch();
           // $q->whereNotNull('sub_owner_id');
        })->orderBy('name')->get();
        $regions = Region::query()->whereHas('cities', function ($q){
            $q->whereHas('dispatcher_contractors', function ($q){
                $q->whereHas('machineries')->forBranch();
            });
        })->with(['cities' => function($q){
            $q->whereHas('dispatcher_contractors', function ($q){
                $q->whereHas('machineries')->forBranch();
            });
        }])->get();

        $brands = Brand::query()->whereHas('machines', function ($q){
            $q->forBranch();
           // $q->whereNotNull('sub_owner_id');
        })->orderBy('name')->get();
        return response()->json([
            'brands' => $brands,
            'regions' => $regions,
            'categories' => Type::setLocaleNames($categories),
        ]);
    }


    function createHelper(Request $request)
    {

        $regions = Region::with('cities')->forDomain()->get();
        $brands = Brand::all();
        $categories = Type::all();
        $contractors  = Contractor::forBranch()->get();


        if (\app()->getLocale() !== 'ru') {
            $categories->each->localization();
            $categories = $categories->sortBy('name')->values()->all();
        }

        return response()->json([
            'regions' => $regions,
            'brands' => $brands,
            'categories' => $categories,
            'contractors' => $contractors,
        ]);
    }
}
