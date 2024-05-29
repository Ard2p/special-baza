<?php

namespace Modules\AdminOffice\Http\Controllers\Support;

use App\Machines\MachineryModel;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Modules\AdminOffice\Entities\Filter;

class ModelsController extends Controller
{
    /**
     * Display a listing of the resource.
     * @return Response
     */
    public function index(Request $request)
    {
        $models = MachineryModel::query()->with('brand', 'category');

        $filter = new Filter($models);
        $filter->getLike([
            'name' => 'name'
        ]);
        $models->orWhereHas('category', function (Builder $q) {
            $filter = new Filter($q);
            $filter->getLike([
                'name' => 'name'
            ]);
        });

        return $models->paginate($request->per_page ?: 15);
    }


    /**
     * Store a newly created resource in storage.
     * @param Request $request
     * @return Response
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|min:2|max:255',
            'description' => 'nullable|string|max:1000',
            'category_id' => 'required|exists:types,id',
            'brand_id' => 'required|exists:brands,id',
        ]);

        $model = MachineryModel::create([
            'category_id' => $request->input('category_id'),
            'brand_id' => $request->input('brand_id'),
            'image' => $request->input('image'),
            'images' => $request->input('images'),
            'name' => $request->input('name'),
            'alias' => generateChpu($request->input('name')),
            'market_price' => numberToPenny($request->input('market_price')),
            'rent_cost' => numberToPenny($request->input('rent_cost')),
            'insurance_cost' => numberToPenny($request->input('insurance_cost')),
            'insurance_without_collateral' => numberToPenny($request->input('insurance_without_collateral')),
            'insurance_service' => numberToPenny($request->input('insurance_service')),
            'insurance_overdue' => numberToPenny($request->input('insurance_overdue')),
            'description' => $request->input('description'),
        ]);

        $characteristics = $request->input('characteristics');

        if ($characteristics && is_array($characteristics)) {

            $model->setCharacteristics($characteristics);

        }

        return $model;
    }

    /**
     * Show the specified resource.
     * @param int $id
     * @return Response
     */
    public function show($id)
    {
        $model = MachineryModel::with('characteristics')->findOrFail($id);

        return $model;
    }


    /**
     * Update the specified resource in storage.
     * @param Request $request
     * @param int $id
     * @return Response
     */
    public function update(Request $request, $id)
    {
        $request->validate([
            'name' => 'required|string|min:2|max:255',
            'description' => 'nullable|string|max:1000',
            'category_id' => 'required|exists:types,id',
            'brand_id' => 'required|exists:brands,id',
        ]);

        $model = MachineryModel::query()->findOrFail($id);

        $model->update([
            'category_id' => $request->input('category_id'),
            'brand_id' => $request->input('brand_id'),
            'image' => $request->input('image'),
            'images' => $request->input('images'),
            'name' => $request->input('name'),
            'alias' => generateChpu($request->input('name')),
            'market_price' => numberToPenny($request->input('market_price')),
            'rent_cost' => numberToPenny($request->input('rent_cost')),
            'insurance_cost' => numberToPenny($request->input('insurance_cost')),
            'insurance_without_collateral' => numberToPenny($request->input('insurance_without_collateral')),
            'insurance_service' => numberToPenny($request->input('insurance_service')),
            'insurance_overdue' => numberToPenny($request->input('insurance_overdue')),
            'description' => $request->input('description'),
        ]);


        $characteristics = $request->input('characteristics');

        if ($characteristics && is_array($characteristics)) {

            $model->setCharacteristics($characteristics);

        }


        return response($model);
    }

    /**
     * Remove the specified resource from storage.
     * @param int $id
     * @return Response
     */
    public function destroy($id)
    {
        $model = MachineryModel::query()->findOrFail($id);

        if ($model->can_delete) {
            $model->delete();
        }

        return response()->json();
    }
}
