<?php

namespace Modules\AdminOffice\Http\Controllers\Support;

use App\Machines\Brand;
use App\Option;
use App\Support\AttributesLocales\BrandLocale;
use App\Support\AttributesLocales\UnitLocale;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Modules\AdminOffice\Entities\Filter;

/**
 * Class BrandsController
 * @package Modules\AdminOffice\Http\Controllers\Support
 */
class BrandsController extends Controller
{

    /**
     * @param Request $request
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function index(Request $request)
    {
        $brands = Brand::query()->with('locale');

        $filter = new Filter($brands);
        $filter->getLike(['name' => 'name']);


        return $brands->paginate($request->input('per_page', 10));
    }

    /**
     * Store a newly created resource in storage.
     * @param Request $request
     * @return Response
     */
    public function store(Request $request)
    {
        $errors = \Validator::make($request->all(), [
            'name' => 'required|unique:brands,name'
        ])->errors()->getMessages();

        if ($errors) {
            return response()->json($errors, 400);
        }

        DB::beginTransaction();
        $brand = Brand::create($request->only('name'));

        foreach ($request->input('locales') as $loc => $locale) {
            BrandLocale::create([
                'name' => $locale,
                'locale' => $loc,
                'brand_id' => $brand->id,
            ]);
        }
        DB::commit();

        $brand->load('locale');

        return  response()->json($brand);
    }


    /**
     * @param $id
     * @return \Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Eloquent\Builder[]|\Illuminate\Database\Eloquent\Collection|\Illuminate\Database\Eloquent\Model
     */
    public function show($id)
    {
        return Brand::query()->with('locale')->findOrFail($id);
    }


    /**
     * @param Request $request
     * @param $id
     * @return \Illuminate\Http\JsonResponse
     * @throws \Exception
     */
    public function update(Request $request, $id)
    {
        $brand = Brand::findOrFail($id);
        $errors = \Validator::make($request->all(), [
            'name' => 'required|unique:brands,name,' . $id
        ])->errors()->getMessages();

        if ($errors) {
            return response()->json($errors, 400);
        }

        DB::beginTransaction();

        $brand->update($request->only('name'));
        $array = $request->all();

        foreach (Option::$systemLocales as $locale) {
            if (!isset($array['locales'][$locale])) {
                continue;
            }
            $loc = $brand->locale()->whereLocale($locale)->first();
            if ($loc) {
                $loc->update([
                    'name' => $array['locales'][$locale]
                ]);
            } else {
                BrandLocale::create([
                    'brand_id' => $brand->id,
                    'name' => $array['locales'][$locale],
                    'locale' => $locale
                ]);
            }
        }
        DB::commit();

        $brand->load('locale');


        return  response()->json($brand);
    }

    /**
     * @param $id
     * @return \Illuminate\Http\JsonResponse
     * @throws \Exception
     */
    public function destroy($id)
    {
        $brand = Brand::query()->whereDoesntHave('machines')->findOrFail($id);
        $brand->delete();

        return \response()->json();
    }
}
