<?php

namespace Modules\AdminOffice\Http\Controllers\Support;

use App\Support\Country;
use App\Support\FederalDistrict;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Validator;

class CountriesController extends Controller
{
    function getCountries(Request $request, $id = null)
    {
        $countries = Country::query();

        if($id) {
            return $countries->findOrFail($id);
        }

        return $countries->paginate($request->input('per_page', 10));
    }


    function create(Request $request)
    {
       $errors = Validator::make($request->all(), [
           'name' => 'required|string',
           'domain_id' => 'nullable|exists:domains,id',
           'alias' => 'required|unique:countries,alias',
       ])->errors()->getMessages();

       if($errors) {
           return \response()->json($errors, 400);
       }

       $country = Country::create([
           'name' => $request->input('name'),
           'alias' => $request->input('alias'),
           'domain_id' => $request->input('domain_id')
       ]);

       return response()->json($country);
    }

    function update(Request $request, $id)
    {
        $country = Country::findOrFail($id);
        $errors = Validator::make($request->all(), [
            'name' => 'required|string',
            'domain_id' => 'nullable|exists:domains,id',
            'alias' => 'required|unique:countries,alias,' . $id,
        ])->errors()->getMessages();

        if($errors) {
            return \response()->json($errors, 400);
        }

        $country = $country->update([
            'name' => $request->input('name'),
            'alias' => $request->input('alias'),
            'domain_id' => $request->input('domain_id')
        ]);

        return response()->json($country);
    }

    function federalDistricts($country_id)
    {
        return FederalDistrict::query()->where('country_id', $country_id)->get();
    }

}
