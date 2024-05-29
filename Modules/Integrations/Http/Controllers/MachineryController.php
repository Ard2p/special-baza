<?php

namespace Modules\Integrations\Http\Controllers;

use App\City;
use App\Machinery;
use App\Support\Gmap;
use App\User;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Modules\Integrations\Rules\Coordinates;

class MachineryController extends Controller
{

    public function __construct()
    {


    }

    private function getStandartRules($data)
    {
        $request = request();
        $request->merge([
            'licence_plate' => trimLicencePlate($request->licence_plate),
            //'type' => $request->category_id,
            'type_eq' => $request->category_id,
        ]);

        $arr = [
            'region_id' => 'required|integer||exists:regions,id',
            'machine_type' => 'required|in:machine,equipment',
            'shift_duration' => 'required|integer|min:1|',
            'city_id' => [
                'required',
                'integer',
                Rule::exists('cities', 'id')->where('region_id', $data['region_id'] ?? 0)
            ],
            'rent_price' => 'required|numeric|min:1|',
            'shift_rent_price' => 'required|numeric|min:1|',
            'brand_id' => 'required|integer|exists:brands,id',
            'photo.*' => 'required|string',
            'scans.*' => 'nullable|string',

        ];

        if (request()->has('coordinates')) {
            $arr = array_merge($arr, [
                'coordinates' => new Coordinates
            ]);
        }

        return $arr;
    }

    private function validateMachinery(Request $request)
    {


        $arr = $this->getStandartRules($request->all());
        $arr['user_id'] = 'required|exists:users,id';

        if ($request->machine_type === 'machine') {
            $arr = array_merge($arr,
                [
                    'category_id' => [
                        'required',
                        Rule::exists('types', 'id')->where('type', 'machine')
                    ],
                   // 'number' => 'required|min:4|string|unique:machineries,number',
                    'licence_plate' => 'required|min:4|string|unique:machineries,number',
                ]);
        } else {
            $arr = array_merge($arr, ['category_id' => [
                'required',
                Rule::exists('types', 'id')->where('type', 'equipment')
            ]]);
        }

        return Validator::make($request->json()->all(), $arr);

    }

    private function validateUpdateMachinery(Request $request, $machinery)
    {

        $arr = $this->getStandartRules($request->all());

        if ($request->machine_type === 'machine') {
            $arr = array_merge($arr,
                [
                    'category_id' => [
                        'required',
                        Rule::exists('types', 'id')->where('type', 'machine')
                    ],
                  //  'number' => "required|min:4|string|unique:machineries,number,{$machinery->id}",
                    'licence_plate' => "required|min:4|string|unique:machineries,number,{$machinery->id}",
                ]);
        } else {
            $arr = array_merge($arr, ['category_id' => [
                'required',
                Rule::exists('types', 'id')->where('type', 'equipment')
            ]]);
        }

        return Validator::make($request->json()->all(), $arr);

    }

    private function getCoordinates($regionName, $city)
    {
        $gmap = new Gmap();
        $coordinates = $gmap->getGeometry('Россия ' . $regionName . ' ' . $city);
        if (!is_array($coordinates)) {
            return null;
        }


        return implode(',', $coordinates);
    }

    private function mapMachinery($machine)
    {
        return Machinery::integrationMap($machine);
    }

    function getVehicle($id)
    {
        $machine = Machinery::whereHas('user', function ($q) {

            $q->currentIntegration();
        })->findOrFail($id);

        return $this->mapMachinery($machine);

    }

    function allVehicles($user_id = null)
    {

        $machines = Machinery::whereHas('user', function ($q) use ($user_id) {
            if ($user_id) {
                $q->where('id', $user_id);
            }
            $q->currentIntegration();
        })->get();

        return $machines->map(function ($machine) {

            return $this->mapMachinery($machine);
        });
    }

    function addVehicle(Request $request)
    {

        try {
            DB::beginTransaction();

            $validate_errors = $this->validateMachinery($request)->errors()->getMessages();

            if ($validate_errors) {
                return response()->json($validate_errors, 400);
            }
            $transbaza_user = User::currentIntegration()->findOrFail($request->user_id);
            $city = City::find($request->city_id);

            $machine_data = [
                'machine_type' => $request->machine_type,
                'address' => $request->address,
                'region_id' => $request->region_id,
                'city_id' => $request->city_id,
                'type' => $request->category_id,
                'brand_id' => $request->brand_id ?: 0,
                'sum_hour' => round(str_replace(',', '.', $request->rent_price) * 100),
                'sum_day' => round(str_replace(',', '.', $request->shift_rent_price) * 100),
                'change_hour' => $request->shift_duration,
                'name' => $request->name,
                'number' => $request->licence_plate,
                'scans' => json_encode([]),
                'user_id' => $transbaza_user->id,
                'coordinates' => $request->coordinates ?: $this->getCoordinates($city->region->name, $city->name),
                'photo' => null

            ];
            $images = ($request->photo);
            $machine = Machinery::create($machine_data);
            if ($images) {
                $machine->putPhotosFromBase64($images);
            }
            $machine->generateChpu(true);
            DB::commit();
        } catch (\Exception $exception) {
            \Log::error($exception->getMessage(), $exception->getTrace());
            return response()->json(['something went wrong'], 400);
        }
        return response()->json($this->mapMachinery(Machinery::find($machine->id)));
    }


    function updateVehicle(Request $request, $id)
    {

        $machine = Machinery::whereHas('user', function ($q) {
            $q->currentIntegration();
        })->findOrFail($id);

        $validate_errors = $this->validateUpdateMachinery($request, $machine)->errors()->getMessages();

        if ($validate_errors) {
            return response()->json($validate_errors, 400);
        }
        $city = City::find($request->city_id);

        $machine_data = [
            'machine_type' => $request->machine_type,
            'address' => $request->address,
            'region_id' => $request->region_id,
            'city_id' => $request->city_id,
            'type' => $request->category_id,
            'brand_id' => $request->brand_id ?: 0,
            'sum_hour' => round(str_replace(',', '.', $request->rent_price) * 100),
            'sum_day' => round(str_replace(',', '.', $request->shift_rent_price) * 100),
            'change_hour' => $request->shift_duration,
            'name' => $request->name,
            'number' => $request->licence_plate,
            'scans' => json_encode([]),
            'coordinates' => $request->coordinates ?: $this->getCoordinates($city->region->name, $city->name),

        ];
        try {
            DB::beginTransaction();
            $images = ($request->photo);
            $machine->update($machine_data);
            if ($request->has('photo')) {
                $machine->putPhotosFromBase64($images);
            }
            $machine->generateChpu(true);
            DB::commit();
        } catch (\Exception $exception) {
            \Log::error($exception->getMessage(), $exception->getTrace());
            return response()->json(['something went wrong'], 400);
        }


        return response()->json($this->mapMachinery($machine));
    }


    function deleteVehicle($id)
    {
        $machine = Machinery::whereHas('user', function ($q) {
            $q->currentIntegration();
        })->findOrFail($id);
        try {
            DB::beginTransaction();

            $machine->freeDays()->delete();
            $machine->work_hours()->delete();
            $machine->forceDelete();
            DB::commit();
        } catch (\Exception $exception) {
            \Log::error($exception->getMessage(), $exception->getTrace());
            return response()->json(['something went wrong'], 400);
        }

        return response()->json();
    }
}
