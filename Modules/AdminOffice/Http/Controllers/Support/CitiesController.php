<?php

namespace Modules\AdminOffice\Http\Controllers\Support;

use App\City;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;

class CitiesController extends Controller
{
   function getCities(Request $request, $country_id, $region_id, $city_id = null)
   {

       $cities = City::query()->with('region')->where('region_id', $region_id);

       if($city_id) {
           return $cities->findOrFail($city_id);
       }

       return $cities->paginate($request->input('per_page', 20));

   }

   function create(Request $request)
   {
       $request->validate([
           'name' => 'required|string',
           'alias' => 'required|string',
           'region_id' => 'required|exists:regions,id',
           'is_capital' => 'nullable|boolean',
       ]);

       $city = City::create([
           'name' => $request->input('name'),
           'region_id' => $request->input('region_id'),
           'is_capital' =>  toBool($request->input('is_capital', 0)),
           'alias' => generateChpu($request->input('alias')),

       ]);

       return response()->json($city);
   }
   function update(Request $request)
   {
        $request->validate([
            'name' => 'required|string',
            'region_id' => 'required|exists:regions,id',
            'is_capital' => 'nullable|boolean',
        ]);

        $city = City::findOrFail($request->route('city_id'));

        $city->update($request->only('name', 'region_id', 'is_capital'));

        return $city;
   }
}
