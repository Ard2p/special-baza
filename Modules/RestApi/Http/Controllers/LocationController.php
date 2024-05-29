<?php

namespace Modules\RestApi\Http\Controllers;

use App\City;
use App\Helpers\RequestHelper;
use App\Http\Sections\Cities;
use App\Machinery;
use App\Machines\Type;
use App\Support\Region;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;

class LocationController extends Controller
{


    function searchPosition(Request $request)
    {
        if ($request->input('type') === 'city') {
            return $this->searchCity($request);
        }
        if ($request->input('type') === 'region') {
            return $this->searchRegion($request);
        }
    }


    function searchCity(Request $request)
    {
        $city = City::with('region')->whereHas('region', function ($q) {
            $q->whereIn('country_id', RequestHelper::requestDomain()->countries->pluck('id')->toArray());
        })
            ->where(function ($q) use ($request) {
                $q->where('name', 'like', "{$request->input('city')}%")
                    ->orWhere('is_capital', 1);
            })
            ->orderBy('is_capital', 'desc')
            ->firstOrFail();

        return $city;
    }

    function searchRegion(Request $request)
    {
        $region = Region::with('cities')
            ->whereIn('country_id', RequestHelper::requestDomain()->countries->pluck('id')->toArray())
            ->where('name', 'like', "%{$request->input('region')}%")->firstOrFail();

        return $region->cities;
    }

    private function getGeoRegion(Request $request)
    {
        return Region::query()->whereHas('cities', function ($q) use ($request) {

            $q->where('cities.id', $request->input('city_id'));
        })
            ->whereIn('country_id', RequestHelper::requestDomain()->countries->pluck('id')->toArray())
            ->firstOrFail();
    }

    function getCategories(Request $request)
    {
        $request_region = $this->getGeoRegion($request);


        $categories = Type::query()->whereHas('machines', function ($q) use ($request_region) {
            $q->where('region_id', $request_region->id);
        })->withCount(['machines' => function ($q) use ($request_region) {
            $q->where('region_id', $request_region->id);
        }])->orderBy('machines_count', 'desc')->limit(8)->get();
        $categories->each->localization();
        $categories = $categories->map(function ($item) use ($request_region) {

            $item->region = $request_region;

            return $item;
        });
        return $categories;

    }

    function getTopCategories(Request $request)
    {

        $request_region = $this->getGeoRegion($request);

        // $cities = City::whereHas('machines')->forDomain()->get() ;
        $cities = $request_region->cities()->whereHas('machines')->limit(6)->get();


        $cities = $cities->map(function ($city) {

            $types = Type::whereHas('machines', function ($q) use ($city) {
                $q->whereCityId($city->id);
            })->withCount('machines')->orderBy('machines_count', 'desc')->limit(2)->get();
            $types->each->localization();
            $types = $types->map(function ($type) use ($city) {
                $type->min = $type->machines()->whereCityId($city->id)->get()->min('sum_day') / 100;
                $type->max = $type->machines()->whereCityId($city->id)->get()->max('sum_day') / 100;

                return $type;
            });

            $city->types = $types;

            return $city;
        });

        return $cities;
    }
}
