<?php

namespace Modules\Integrations\Http\Controllers;

use App\City;
use App\Machines\Brand;
use App\Machines\Type;
use App\Support\Region;
use Exception;
use GuzzleHttp\Client;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Log;

class HelpersDataController extends Controller
{

    private function mapRegion(Region $region)
    {
        return [
            'id' => $region->id,
            'name' => $region->name
        ];
    }

    private function mapCity(City $city)
    {
        return [
            'id' => $city->id,
            'name' => $city->name,
            'region_id' => $city->region_id,
        ];
    }

    function getRegions($id = null)
    {
        $regions = Region::whereCountryId(1);

        if ($id) {
            return $this->mapRegion($regions->findOrFail($id));
        }

        return $regions->get()->map->only(['id', 'name']);

    }


    function getCities($region_id, $city_id = null)
    {
        $cities = City::query()
            ->whereRegionId($region_id)
            ->whereHas('region', function ($q) {
                $q->whereCountryId(1);
            });

        if ($city_id) {
            return $this->mapCity($cities->findOrFail($city_id));
        }

        return $cities->get()->map->only(['id', 'name', 'region_id']);
    }

    function getAllCities()
    {
        $cities = City::query()
            ->whereHas('region', function ($q) {
                $q->whereCountryId(1);
            });

        return $cities->get()->map->only(['id', 'name', 'region_id']);
    }


    function getCategories($id = null)
    {
        $categories = Type::all();

        if($id){
            return $this->mapCategory(Type::findOrFail($id));
        }

        return $categories->map->only(['id', 'name', 'type']);
    }

    function mapBrand(Brand $brand)
    {
        return [
            'id' => $brand->id,
            'name' => $brand->name,
        ];
    }

    function mapCategory(Type $category)
    {
        return [
            'id' => $category->id,
            'name' => $category->name,
            'type' => $category->type,
        ];
    }


    function getBrands($id = null)
    {
        $brands = Brand::all();

        if($id){
            return $this->mapBrand(Brand::findOrFail($id));
        }

        return $brands->map->only(['id', 'name']);
    }

    function login(Request $request)
    {
        $http = new Client();

       /* Log::info('try connect ' . $request->login);*/
        $accept = [
            'test@lorus.com',
            'fms@c-cars.tech',
        ];
        if (!in_array($request->login, $accept)) {
            Log::info('incorrect login ' . $request->login);
            return response()->json(['Unauthorized'], 401);
        }

        try {
            $response = $http->post(env('APP_URL') . '/oauth/token', [
                'form_params' => [
                    'grant_type' => 'password',
                    'client_id' => '2',
                    'client_secret' => '1xsvSckLvcDoNP18ByXC1jxOKwGh3io86msA4tn8',
                    'username' => $request->login,
                    'password' => $request->password,
                    'scope' => '',
                ],
            ]);
        } catch (Exception $e) {
            Log::error('Fail');
            return response()->json(['Unauthorized'], 401);
        }

        $response = json_decode((string)$response->getBody(), true);

        return response()->json([
            'token' => $response['access_token'],
            'expires' => $response['expires_in'],
        ]);
    }

}
