<?php

namespace Modules\RestApi\Http\Controllers;

use App\City;
use App\Machinery;
use App\Machines\OptionalAttribute;
use App\Service\RequestBranch;
use App\Support\Gmap;
use App\Support\Region;
use App\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Modules\AdminOffice\Entities\Filter;
use Modules\CompanyOffice\Entities\Company\CompanyBranch;
use Modules\ContractorOffice\Entities\Vehicle\Price;
use Modules\ContractorOffice\Services\Tariffs\TimeCalculation;
use Modules\Orders\Entities\OrderManagement;
use Modules\RestApi\Transformers\VehicleSearch;

class SearchController extends Controller
{


    private function searchByDates(Request $request, $machines)
    {
        $data = $request->only(['date_from', 'date_to', 'time_from', 'time_to']);
        $errors = \Validator::make($data, [
            'date_from' => 'date|date_format:Y-m-d|nullable',
            'date_to' => 'date|date_format:Y-m-d|nullable',
            'time_from' => 'date_format:H:i|nullable',
            'time_to' => 'date_format:H:i|nullable',
            'hour_cost_from' => 'numeric|nullable',
            'hour_cost_to' => 'numeric|nullable',
            'change_cost_to' => 'numeric|nullable',
            'change_cost_from' => 'numeric|nullable',
        ])->errors()->getMessages();

        if ($errors) {

            return \response()->json($errors, 400);
        }
        if ($request->date_from || $request->date_to) {
            $machines->whereDoesntHave('freeDays', function ($q) use ($request) {

                $date_from = $request->date_from ? Carbon::createFromFormat('Y-m-d H:i', $request->date_from . " " . ($request->time_from ?: '00:00')) : false;
                $date_to = $request->date_to ? Carbon::createFromFormat('Y-m-d H:i', $request->date_to . " " . ($request->time_to ?: '00:00')) : false;
                if ($date_from && !$date_to) {
                    $q->where('startDate', '<=', $date_from);
                    $q->where('endDate', '>=', $date_from);
                }

                if (!$date_from && $date_to) {
                    $q->where('startDate', '=<', $date_to);
                    $q->where('endDate', '>=', $date_to);
                }
                if ($date_from && $date_to) {
                    $q->where(function ($q) use ($date_from, $date_to) {
                        $q->whereBetween('startDate', [
                            $date_from,
                            $date_to,
                        ])->orWhereBetween('endDate', [
                            $date_from,
                            $date_to,
                        ]);
                    });

                }
            });
        }

        return $machines;
    }

    function optionalFilters(Request $request, Builder $query)
    {
        $input = $request->all();

        $filtered = array_filter($input, function ($k) {

            return Str::contains($k, 'filter_');
        }, ARRAY_FILTER_USE_KEY);


        // $attribute = OptionalAttribute::whereTypeId($request->input('category_id'))->find($id);

        // if ($attribute && $attribute->field === 'number') {

        if(count($filtered) === 0){
            return;
        }
        $query->whereHas('optional_attributes', function (Builder $q) use ($filtered) {

            foreach ($filtered as $key => $filter) {
                $explode = explode('_', $key);

                if (isset($explode[1])) {
                    $id = $explode[1];
                    $filter = explode(',', $filter);
                    $q->where(function (Builder $q) use ($id, $filter) {
                        $q->where('optional_attributes.id', $id)
                            ->where('optional_attributes.field_type', 2)
                            ->whereRaw('CAST(attribute_machine.value AS DOUBLE) between ? and ?', $filter);
                    })
                        ->orWhereRaw('NOT EXISTS(SELECT * FROM attribute_machine WHERE `attribute_machine`.`optional_attribute_id` = `optional_attributes`.`id` AND `attribute_machine`.`machinery_id` = `machineries`.`id`)');

                }
            }


        });
        // }

    }

    private function prepareQuery(Request $request, $query)
    {

        $this->optionalFilters($request, $query);
        $filter = new Filter($query);

        $filter->getEqual([
            'category_id' => 'type',
            'model_id' => 'model_id',
            'brand_id' => 'brand_id',
        ]);

        if($request->anyFilled(['shift_cost_from', 'shift_cost_to'])) {
            $query->searchByCost(TimeCalculation::TIME_TYPE_SHIFT, numberToPenny(request('shift_cost_from', 0)),  numberToPenny(request('shift_cost_to', 0)));
        }
        if($request->anyFilled(['hour_cost_from', 'hour_cost_to'])) {
            $query->searchByCost(TimeCalculation::TIME_TYPE_HOUR, numberToPenny(request('hour_cost_from', 0)),  numberToPenny(request('hour_cost_to', 0)));
        }

        $query->forDomain();

        /*   if (config('in_mode')) {
               $query->whereHas('region', function ($q) {
                   $q->whereCountryId(5);
               });
           }*/

        if ($request->filled('region_id') && !$request->filled('city_id')) {

            $query->whereRegionId($request->region_id);
        }

        if ($request->filled('city_id')) {


            $query->where(function ($query) use ($request) {

                $city = City::find($request->city_id);

                if ($city) {

                    $query->where('city_id', $request->city_id);
                    if ($city->coordinates) {

                        $query->orWhereInCircle($city->coordinates['lat'], $city->coordinates['lng']);
                    }

                }
            });

        }
        if ($request->filled('with_photo') && filter_var($request->with_photo, FILTER_VALIDATE_BOOLEAN)) {

            $query->whereNotIn('photo', ['null', json_encode([])]);
        }
        if (app(RequestBranch::class)->company) {
            $query->companyRented()->forCompany();
            if (app(RequestBranch::class)->companyBranch) {
                $query->forBranch();
            }

        } else {
            $query->rented();
        }

        $this->searchByDates($request, $query);

    }

    function searchMachines(Request $request)
    {
        $machines = Machinery::with('region', 'city', '_type', 'work_hours', 'optional_attributes');

        $this->prepareQuery($request, $machines);

        if ($request->filled('sort_by')) {
            $sort = $request->input('sort_by');

            $machines->orderBy($sort === 'created_at' ? $sort : 'sum_day', $sort === 'sum' ? 'asc' : 'desc');
        } else {
            $machines->orderBy('telematics_type', 'desc');
        }

        $machines = $machines
            /*   ->whereIn('id', function ($query) use ($count){
               $query->select('id')->from('machineries')->groupBy('user_id')->havingRaw('count(*) > ?', [$count]);
           })*/

            /*  ->whereHas('user', function ($q) use ($count, $request) {

                  $q->whereHas('machines', function ($query) use ($request) {
                      $this->prepareQuery($request, $query);

                  }, '>=', $count);
              })*/

            /*->groupBy('user_id')
            ->havingRaw('COUNT(*) >= ?', [$count])*/
            ->paginate(8);

        return response()->json([
            'data' => VehicleSearch::collection($machines),
            'total' => $machines->total(),
            'count' => $machines->count(),
            'per_page' => $machines->perPage(),
            'current_page' => $machines->currentPage(),
            'total_pages' => $machines->lastPage(),
            'last_page' => $machines->lastPage(),
            'next_page_url' => $machines->nextPageUrl(),
        ]);
    }


    private function prepareDates(Request $request)
    {
        $date_from = $request->date_from ? Carbon::createFromFormat('Y-m-d', $request->date_from) : now()->addDay();
        $date_to = $request->date_to ? Carbon::createFromFormat('Y-m-d', $request->date_to) : (clone $date_from);
        /*        $time_from =  $request->time_from ? : $date_from->format('H:i');
                $time_to =  $request->time_to ?: $time_from;*/

        $request->merge([
            'date_from' => (string)$date_from->format('Y-m-d'),
            'date_to' => (string)$date_to->format('Y-m-d'),
            'count' => 1
            /*  'date_from' => ((string) $date_from->format('Y-m-d')) . ' ' . $time_from,
              'date_to' => ((string) $date_to->format('Y-m-d')) . ' ' . $time_to,
              'count' => $request->count ?: 1 */
        ]);
    }

    function prepareOrderDetails(Request $request)
    {
        $vehicle = Machinery::with('work_hours', 'optional_attributes')->findOrFail($request->id);

        try {
            $this->prepareDates($request);

        } catch (\Exception $exception) {

        }
        $request->validate([
            'id' => 'required|integer',
            'date_from' => 'required|date|date_format:Y-m-d|after:' . now()->format('Y-m-d') . '|before_or_equal:' . $request->date_to,
            'date_to' => 'required|date|date_format:Y-m-d',
            /*    'date_from' => 'required|date|date_format:Y-m-d H:i|after:' . now()->addDay()->format('Y-m-d'),
                'date_to' => 'required|date|date_format:Y-m-d H:i',*/
            'count' => 'required|integer|min:1',
        ]);

        $date_from = Carbon::createFromFormat('Y-m-d', $request->date_from);
        $date_to = Carbon::createFromFormat('Y-m-d', $request->date_to);


        $branch = CompanyBranch::with(['machines' => function ($q) use ($vehicle, $date_from, $date_to) {
            $q->with('work_hours');
            $q->whereType($vehicle->type)
                //->rented()
                ->checkAvailable($date_from, $date_to);
            $q->where(function ($query) use ($vehicle) {

                if ($vehicle->city->coordinates) {
                    $query->orWhereInCircle($vehicle->city->coordinates['lat'], $vehicle->city->coordinates['lng']);
                    $query->orWhere('city_id', $vehicle->city_id);
                }

            });

        }])->findOrFail($vehicle->company_branch_id);

        // logger($branch->machines->count());
        if ($branch->machines->count() < 1) {
            $errors[] = ['Требуемая техника не найдена.'];
        }
        $days_count = $date_to->diffInDays($date_from) + 1;

        if ($days_count < 1) {
            $errors[] = ['Слишком короткий заказ.'];
        }

        if ($errors ?? false) {
            return response()->json($errors, 400);
        }

        //$needle = ($request->count ?: 1) - 1;
        $needle = 0;
        $day_cost = 0;

        $order_vehicles = collect([]);
        $order_vehicles->push($vehicle);

        $day_cost += $vehicle->sum_day / 100;

        $machines = $branch->machines->filter(function ($machine) use ($vehicle) {
            return $machine->id !== $vehicle->id;
        });
        foreach ($machines as $machine) {
            if ($needle === 0) {
                break;
            }
            $day_cost += $machine->sum_day / 100;
            $order_vehicles->push($machine);
            --$needle;
        }

        return response()->json([
            'date_from' => $date_from->format('d.m.Y'),
            'date_to' => $date_to->format('d.m.Y'),
            /*         'time_from' => $date_from->format('H:i'),
                     'time_to' => $date_to->format('H:i'),*/
            'days_count' => $days_count,
            'full_cost' => $day_cost * $days_count,
            'day_cost' => $day_cost,
            'order_vehicles' => VehicleSearch::collection($order_vehicles),
            'vehicles_count' => $order_vehicles->count(),
        ]);
    }


    function getByAlias($alias)
    {
        $vehicle = Machinery::with('optional_attributes', 'freeDays')->whereAlias($alias)->firstOrFail();

        return response()->json(VehicleSearch::make($vehicle));
    }

    function calculateCost(Request $request, $id)
    {
        try {
            $this->prepareDates($request);

        } catch (\Exception $exception) {

        }

        $vehicle = Machinery::with('work_hours', 'optional_attributes')->findOrFail($id);
        $request->validate([
            'date_from' => 'required|date|date_format:Y-m-d|after:' . now()->format('Y-m-d') . '|before_or_equal:' . $request->date_to,
            'date_to' => 'required|date|date_format:Y-m-d',
        ]);


        $date_from = Carbon::parse($request->date_from);
        $date_to = Carbon::parse($request->date_to);

        $cost = $vehicle->calculateCostByDates($date_from, $date_to);

        return response()->json([
            'full_cost' => $cost / 100,
            'full_cost_format' => humanSumFormat($cost),
            'shifts' => $date_to->diffInDays($date_from) + 1,
            'cost' => $vehicle->sum_day / 100,
            'cost_format' => $vehicle->sum_day_format,
            'order_vehicles' => [VehicleSearch::make($vehicle)],
            'vehicles_count' => 1,
            'date_from' => $date_from->format('d.m.Y'),
            'date_to' => $date_to->format('d.m.Y'),
        ]);

    }


}
