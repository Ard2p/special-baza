<?php

namespace Modules\AdminOffice\Http\Controllers\Warehouse;

use App\Service\RequestBranch;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Modules\AdminOffice\Entities\Filter;
use Modules\AdminOffice\Http\Requests\WarehousePartRequest;
use Modules\CompanyOffice\Entities\Company\CompanyBranch;
use Modules\PartsWarehouse\Entities\Warehouse\Part;

class PartsController extends Controller
{

    private CompanyBranch $branch;

    public function __construct(RequestBranch $branch)
    {
        //$this->branch = $branch->companyBranch;
    }

    /**
     * Display a listing of the resource.
     * @return Response
     */
    public function index(Request $request)
    {
        $parts = Part::query();

        $filter = new Filter($parts);
        $filter->getEqual([
            'brand_id' => 'brand_id',
            'group_id' => 'group_id',
        ]);
        if ($request->filled('category_id')) {
            $parts->whereHas('models', function ($q) use ($request) {
                $q->where('machinery_models.category_id', $request->input('category_id'));
            });

        }
        return $parts->get(/*$request->per_page ?: 15*/);
    }

    /**
     * Store a newly created resource in storage.
     * @param WarehousePartRequest $request
     * @return Response
     * @throws \Exception
     */
    public function store(WarehousePartRequest $request)
    {
        DB::beginTransaction();

        /** @var Part $part */
        $part = Part::create([
            'name' => $request->input('name'),
            'vendor_code' => $request->input('vendor_code'),
            'brand_id' => $request->input('brand_id'),
            'group_id' => $request->input('group_id'),
            'unit_id' => $request->input('unit_id'),
            'images' => $request->input('images'),
        ]);

        foreach ($request->input('models') as $item) {
            $part->models()->syncWithoutDetaching([
                $item['model_id'] => [
                    'serial_numbers' => $item['serial_numbers'] ?? null
                ]
            ]);
        }

        if ($request->filled('analogue_id')) {
            $analogue = Part::query()->findOrFail($request->input('analogue_id'));
            $group = $analogue->getAnalogueGroup();
            $part->setAnalogue($group);
        }

        DB::commit();

        return response()->json($part);
    }

    /**
     * Show the specified resource.
     * @param int $id
     * @return Response
     */
    public function show($id)
    {
        return view('adminoffice::show');
    }

    /**
     * Update the specified resource in storage.
     * @param Request $request
     * @param int $id
     * @return Response
     */
    public function update(WarehousePartRequest $request, $id)
    {
        DB::beginTransaction();

        $part = Part::query()->findOrFail($id);
        /** @var Part $part */
        $part->update([
            'name' => $request->input('name'),
            'vendor_code' => $request->input('vendor_code'),
            'brand_id' => $request->input('brand_id'),
            'group_id' => $request->input('group_id'),
            'unit_id' => $request->input('unit_id'),
            'images' => $request->input('images'),
        ]);
        $part->models()->detach();

        foreach ($request->input('models') as $item) {
            $part->models()->attach([
                $item['model_id'] => [
                    'serial_numbers' => $item['serial_numbers']
                ]
            ]);
        }

        if ($request->filled('analogue_id')) {
            $analogue = Part::query()->findOrFail($request->input('analogue_id'));
            $group = $analogue->getAnalogueGroup();
            $part->setAnalogue($group);
        }

        DB::commit();

        return response()->json($part);
    }

    function detachAnalogue($id)
    {
        /** @var Part $part */
        $part = Part::query()->findOrFail($id);

        $part->setAnalogue(null);

        return \response()->json();
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

    public function partsCheck(Request $request)
    {
       $part = $this->branch->warehouse_parts()->wherePivot('company_branches_warehouse_parts.vendor_code', $request->input('vendor_code'))
           ->orWhere('warehouse_parts.vendor_code', $request->input('vendor_code'))->first();

       return $part;
    }
}
