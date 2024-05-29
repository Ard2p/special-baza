<?php

namespace Modules\AdminOffice\Http\Controllers;

use App\City;
use App\Machinery;
use App\Machines\OptionalAttribute;
use App\Machines\Type;
use App\Support\Gmap;
use App\System\Audit;
use App\User;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Modules\AdminOffice\Entities\Filter;
use Modules\AdminOffice\Services\RoleService;
use Modules\CompanyOffice\Entities\Company\CompanyBranch;
use Modules\ContractorOffice\Http\Requests\CreateVehicle;
use Modules\ContractorOffice\Services\VehicleService;
use Modules\ContractorOffice\Transformers\Vehicle;
use Modules\Integrations\Rules\Coordinates;
use Modules\RestApi\Entities\Currency;

class  VehiclesController extends Controller
{

    public function __construct()
    {
        $this->middleware('accessCheck:'. RoleService::ADMIN_VEHICLES .',show')->only('getVehicles', 'getAudit', 'userVehicles', 'getOptionalAttributes');
        $this->middleware('accessCheck:' .  RoleService::ADMIN_VEHICLES)->only('update', 'store');

    }

    private function filterVehicles(Request $request, $vehicles)
    {

        $filter = new Filter($vehicles);
        $filter->getLike([
            'name' => 'name',
            'id' => 'id',
            'number' => 'number',
        ])->getEqual([
            'region' => 'region_id',
            'city' => 'city_id',
            'status' => 'status',
            'type' => 'type',
        ])->getBetween([
            'sum_day_from' => 'sum_day',
            'sum_day_to' => 'sum_day',
        ], true)->getBetween([
            'sum_hour_from' => 'sum_hour',
            'sum_hour_to' => 'sum_hour',
        ], true);
        if (Auth::user()->isRegionalRepresentative() && !Auth::user()->isSuperAdmin()) {
            $vehicles->forRegionalRepresentative();
        }

        if ($request->filled('user')) {

            $vehicles->whereHas('user', function ($q) use ($request) {
                $q->where('email', 'like', "%{$request->input('user')}%")
                    ->orWhere('phone', 'like', "%{$request->input('user')}%");
            });

        }

        if ($request->filled('regional_representative')) {

            $vehicles->whereHas('regional_representative', function ($q) use ($request) {
                $q->where('email', 'like', "%{$request->input('regional_representative')}%")
                    ->orWhere('phone', 'like', "%{$request->input('regional_representative')}%");
            });

        }
    }

    function getVehicles(Request $request, $id = null)
    {
        $vehicles = Machinery::with('region', 'city', 'user', 'regional_representative', '_type')->forDomain();

        if ($id) {
            $vehicle = $vehicles->findOrFail($id);
            $vehicle->load(['freeDays', 'orders']);

            return response()->json(Vehicle::make($vehicle));
        }

        $this->filterVehicles($request, $vehicles);

        return $vehicles->orderBy('created_at', 'DESC')->paginate($request->per_page ?: 10);
    }

    function getAudit(Request $request, $id)
    {
        $audits = Audit::query()->with('user')
            ->where('auditable_type', Machinery::class)
            ->where('auditable_id', $id)
            ->orderBy('created_at', 'desc')
            ->paginate($request->input('per_page', 10));

        return $audits;
    }

    function getOptionalAttributes(Request $request)
    {
        $options = OptionalAttribute::query();

        if ($request->filled('category_id')) {
            $options->whereTypeId($request->category_id);
        }

        return $options->get();
    }


    function prepareAttributes($attributes, Type $category)
    {
        $arr = [];
        foreach ($attributes as $id => $attribute) {
            if (!$attribute || !$category->optional_attributes()->find($id)) {
                continue;
            }
            $arr[$id] = ['value' => $attribute];
        }
        return $arr;
    }

    function store(CreateVehicle $request)
    {
        $user = CompanyBranch::findOrFail($request->company_branch_id);

        $service = new VehicleService($user);
        try {

            $machine = $service->setData($request->all())->createVehicle();


            if($request->input('telematics_type') === 'trekerserver'){
                if(!$machine->attachTrekerServerTelematic()) {
                    DB::rollBack();
                    return \response()->json(['vin' => ['Не найден в базе Trekerserver.ru']], 400);
                };
            }

        } catch (\Exception $exception) {
            DB::rollBack();
            \Log::error($exception->getMessage(), $exception->getTrace());

            return response()->json(['something went wrong'], 400);
        }
        DB::commit();
        return Vehicle::make($machine);
    }

    function update(CreateVehicle $request, $id)
    {
        $machine = Machinery::query();

        if (Auth::user()->isRegionalRepresentative() && !Auth::user()->isSuperAdmin()) {
            $machine->forRegionalRepresentative();
        }
        $machine =  $machine->findOrFail($id);

        $service = new VehicleService($machine->company_branch);


        try {
            DB::beginTransaction();

            $machine = $service->setData($request->all())->updateVehicle($id);

            if($request->input('telematics_type') === 'none'){
                $machine->detachTelematics();
            }

            if($request->input('telematics_type') === 'trekerserver'){
                if(!$machine->attachTrekerServerTelematic()) {
                    DB::rollBack();
                    return \response()->json(['vin' => ['Не найден в базе Trekerserver.ru']], 400);
                };
            }

            DB::commit();
        } catch (\Exception $exception) {
            \Log::error($exception->getMessage(), $exception->getTrace());
            return response()->json(['something went wrong'], 400);
        }

        return response()->json(Vehicle::make($machine));
    }

    private function getCoordinates($regionName, $city)
    {

        return Gmap::getCoordinatesByAddress($regionName, $city);

    }

    function userVehicles(Request $request, $id)
    {
        $company_branch = CompanyBranch::findOrFail($id);

        $vehicles = Machinery::query()->where('company_branch_id', $company_branch->id)->with('region', 'city', 'user', 'regional_representative', '_type');

        $this->filterVehicles($request, $vehicles);

        return $vehicles->orderBy('created_at', 'DESC')->paginate($request->per_page ?: 10);
    }

}
