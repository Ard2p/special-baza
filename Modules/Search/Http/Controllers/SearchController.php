<?php

namespace Modules\Search\Http\Controllers;

use App\City;
use App\Machinery;
use App\Machines\Type;
use App\Support\Region;
use App\User;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;

class SearchController extends Controller
{

    function directoryMain(Request $request)
    {
        $cats = Type::whereHas('machines')->orderBy('name')->get();

        return view('special_categories.index', compact('cats'));
    }


    function directoryMainCategory(Request $request, $category, $region_alias = null, $city_alias = null)
    {

        $category = Type::whereAlias($category)->firstOrFail();
        $current_city = false;
        $current_region = false;

        //$categories = Type::whereHas('machines')->orderBy('name')->get();

        /*$regions = Region::with(['cities' => function ($q) use ($request, $category) {
            if ($request->filled('city_id')) {
                $q->whereId($request->city_id);
            }
            $q->whereHas('machines', function ($q) use ($category) {
                $q->whereType($category->id);
            });
        }]);*/
      /*  ->whereHas('machines', function ($q) use ($category) {
            $q->whereType($category->id);
        });*/

/*        $regions = $regions->whereCountry('russia')->get();

        $machines = Machinery::with('region', 'city', '_type')->whereType($category->id);*/

        if ($region_alias) {

         /*   $machines->whereHas('region', function ($q) use ($region_alias){
                $q->whereAlias($region_alias);
            });*/

            $current_region = Region::where('alias', $region_alias)->with('cities')->firstOrFail();

        }

        if ($city_alias) {
          /*  $machines->whereHas('city', function ($q) use ($city_alias){
                $q->whereAlias($city_alias);
            });*/

            $current_city = $current_region->cities->where('alias', $city_alias)->first();
        }

      //  $machines = $machines->paginate(4);

        return view('new-front.index', compact('category', 'category', 'current_region', 'current_city'));
    }

    function showRent($category, $region, $city, $alias)
    {
        $category = Type::whereAlias($category)->firstOrFail();

        $region = Region::whereAlias($region)->firstOrFail();
        // dd($region->id);
        $city = City::whereAlias($city)->whereRegionId($region->id)->firstOrFail();

        $machine = Machinery::whereCityId($city->id)->whereType($category->id)->whereAlias($alias)->firstOrFail();
        $time_type = Machinery::getTimeType();
        return  \response()->json();//view('user.machines.rent_page', compact('machine', 'time_type'));
    }
}
