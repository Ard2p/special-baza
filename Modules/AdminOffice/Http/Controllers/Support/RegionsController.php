<?php

namespace Modules\AdminOffice\Http\Controllers\Support;

use App\Support\Region;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Validator;

class RegionsController extends Controller
{


    function getRegions(Request $request, $country_id, $region_id = null)
    {
      $regions = Region::query()->with('country', 'federal_district')->where('country_id', $country_id);

      if($region_id) {

          return $regions->findOrFail($region_id);
      }

      return $regions->paginate($request->input('per_page', 10));
    }

    function create(Request $request, $country_id)
    {
        $errors = Validator::make($request->all(), [
            'name' => 'required|string',
            'number' => 'required',
            'style_name' => 'required|string',
            'federal_district_id' => 'nullable|exists:federal_districts,id',
            'alias' => 'required|string|unique:regions,alias',
        ])
            ->errors()
            ->getMessages();

        if($errors) {
            return response()->json($errors, 400);
        }

        $region = Region::create([
            'name' =>  $request->input('name'),
            'number' =>  $request->input('number'),
            'style_name' =>  $request->input('style_name'),
            'federal_district_id' =>  $request->input('federal_district_id'),
            'alias' =>  $request->input('alias'),
            'country_id' =>  $country_id,
        ]);

        return $region;
    }

    function update(Request $request, $country_id, $region_id)
    {
        $region = Region::findOrFail($region_id);

        $errors = Validator::make($request->all(), [
            'name' => 'required|string',
            'number' => 'required',
            'style_name' => 'required|string',
            'federal_district_id' => 'nullable|exists:federal_districts,id',
            'alias' => 'required|string|unique:regions,alias,' . $region->id,
        ])
            ->errors()
            ->getMessages();

        if($errors) {
            return response()->json($errors, 400);
        }

        $region->update([
          'name' =>  $request->input('name'),
          'number' =>  $request->input('number'),
          'style_name' =>  $request->input('style_name'),
          'federal_district_id' =>  $request->input('federal_district_id'),
          'alias' =>  $request->input('alias'),
        ]);

        return $region;
    }
}
