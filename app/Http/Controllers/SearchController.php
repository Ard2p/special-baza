<?php

namespace App\Http\Controllers;

use App\City;
use App\Machinery;
use App\Machines\Brand;
use App\Machines\SearchFilter;
use App\Machines\Type;
use App\Option;

use App\Search;
use App\Service\OrderService;
use App\Support\Gmap;
use App\Support\Region;
use App\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class SearchController extends Controller
{

    function getRequiredFields()
    {
        return [
            'region' => 'required|integer',
            'date' => 'required|date|after:' . Carbon::now()->format('Y-m-d H:i'),
            'days' => 'required|integer|min:1|',
            'type' => 'required|integer',
            'address' => 'required|string|max:255',
        ];
    }

    function getFieldsMessage()
    {
        return [
            'region.required' => trans('transbaza_proposal.validate_region'),
            'region.integer' =>trans('transbaza_proposal.validate_region'),
            'type.required' => trans('transbaza_proposal.validate_type'),
            'type.integer' => trans('transbaza_proposal.validate_type'),
            'type.exists' => trans('transbaza_proposal.validate_type'),
            'date.required' => trans('transbaza_proposal.validate_date'),
            'date.date' => trans('transbaza_proposal.validate_date_format'),
            'date.after' => trans('transbaza_proposal.validate_date_after'),
            'days.required' => trans('transbaza_proposal.validate_days'),
            'days.integer' => trans('transbaza_proposal.validate_days_int'),
            'days.min' => trans('transbaza_proposal.validate_days_min'),
            'brand.integer' => trans('transbaza_proposal.validate_brand'),
            'address.required' => trans('transbaza_proposal.validate_address'),
            'city_id.required' => trans('transbaza_proposal.validate_city'),
            'city_id.integer' => trans('transbaza_proposal.validate_city'),
        ];
    }

    function index(Request $request)
    {
        $searches = Search::currentUser()->get();

        $regions = Region::whereCountry('russia')->get();
        if ($request->exists('list')) {

            return (!$request->ajax())
                ? view('search.list', compact('searches'))
                : response()->json(['data' => $searches]);
        }
        return view('search', ['searches' => $searches, 'regions' => $regions]);
    }


    function validateSimpleStep(Request $request, $step)
    {
        $rules = [];
        if ($request->filled('date') && $request->filled('time')) {
            $request->merge(['date' => $request->date . ' ' . $request->time]);
        }

        switch ($step) {
            case 1:
                $rules = [
                    'region' => 'required|exists:regions,id',
                    'city_id' => 'required|exists:cities,id',
                    'time' => 'required|string',
                    'date' => 'required|date|after:' . Carbon::now()->format('Y-m-d H:i'),
                    'date_end' => 'required|date|after:' . Carbon::now()->format('Y-m-d'),
                    // 'days' => 'required|integer|min:1|',
                    'address' => 'required|string|max:255',
                ];

                break;
            case 2:
                $rules = array_merge($rules, [
                    'type' => 'required|exists:types,id',
                ]);
                break;
        }
        $errors = Validator::make($request->all(), $rules, $this->getFieldsMessage())
            ->errors()
            ->getMessages();
        $city = City::whereRegionId($request->region)->find($request->city_id);


        if (!$city && $request->filled('city_id')) {
            $errors['city_id'][] = 'Город не найден в выбраном регионе';
        }

        if ($errors) return response()->json($errors, 419);


        return response()->json(['message' => '']);

    }

    function modifyQuery($machine, $request, $id)
    {


        if ($request->filled('brand')) {

            $machine->where('brand_id', $request->input('brand'));
        }
        #  if ($request->input('type') !== '0') {

        $machine->whereType($id);
        #  }
        if ($request->filled('sum_hour') && $request->filled('sum_day')) {

            $machine->where('sum_hour', '<=', $request->input('sum_hour') * 100);
            $machine->where('sum_day', '<=', $request->input('sum_day') * 100);

        } elseif ($request->filled('sum_hour')) {

            $machine->where('sum_hour', '<=', $request->input('sum_hour') * 100);

        } elseif ($request->filled('sum_day')) {

            $machine->where('sum_day', '<=', $request->input('sum_day') * 100);

        }
        $date_from = Carbon::createFromFormat('Y/m/d H:i', $request->input('date'))->startOfDay();

        $date_to = Carbon::createFromFormat('Y/m/d H:i', $request->input('date_end'))->startOfDay();
        //->addDays($request->input('days') - 1);

        $machine->where('region_id', $request->input('region'))
            ->where('city_id', $request->input('city_id'))
            ->checkAvailable($date_from, $date_to);

        return $machine;
    }

    function getCollection(Request $request, $ids)
    {
        $request->merge([
            'date' => $request->date . ' ' . $request->time,

        ]);
        $users = User::query();
        $users->with(['machines' => function ($q) use ($request, $ids) {
            //  $q = $this->modifyQuery($q, $request, $id);
            $q->whereIn('type', $ids->pluck('id'));
            $q->with('brand', '_type', 'region', 'freeDays', 'user');
        }]);
        foreach ($ids as $id) {

            $users->whereHas('machines', function ($q) use ($request, $id) {
                //    $machine = Machinery::with('brand', '_type', 'region', 'freeDays', 'user');
                $this->modifyQuery($q, $request, $id['id']);
            }, '>=', count($ids->where('id', $id['id'])));
        }


        return $users->get();
    }


    function getResult(Request $request)
    {
        if($request->filled('date') && $request->filled('time') && $request->filled('date_end')){
            $request->merge([
                'days' => Carbon::createFromFormat('Y/m/d H:i', $request->date . ' ' . $request->time)->startOfDay()
                        ->diffInDays(Carbon::createFromFormat('Y/m/d H:i', $request->date_end . ' ' . $request->time)->startOfDay()) + 1,
                'date_end' => $request->date_end . ' ' . $request->time
            ]);
        }


        $service = new OrderService($request);

        if ($service->validateErrors()->getErrors()) return response()->json($service->getErrors(), 419);


        $collection = $service->search()->search_collection;
        $coordinates = $service->coordinates;


        if ($collection->isEmpty()) {

            $data = [
                'data' => [],
                'status' => 2,
                'view' => view('search.not_found')
                    ->with('request', $request->all())
                    ->render(),
            ];
        } else {

            $data = [
                'lat' => $coordinates['lat'],
                'lng' => $coordinates['lng'],
                'collection' => $collection->toArray(),
                'data' => view('search.search_result')->withRequest($request)
                    ->with('collection', $collection)
                    ->with('representative_commission', (Option::find('representative_commission')->value ?? 0) / 100)
                    //  ->with('system_commission', $system_commission)
                    ->render(),
                'status' => 1
            ];

        }
        return $data;

    }

    function checkProposal(Request $request)
    {

        $service = new OrderService($request);

        if ($service->validateErrors()->getErrors()) return response()->json($service->getErrors(), 419);


        return response()->json([
            'view' => view('search.proposal_form')
                ->with('request', $request)
                ->render(),
        ]);
    }

    function store(Request $request)
    {
        $errors = Validator::make($request->all(), $this->getRequiredFields(), $this->getFieldsMessage())
            ->errors()
            ->all();
        if ($errors) return response()->json($errors, 419);

        $data = $request->except('_token');

        Search::create([
            'name' => $request->input('name'),
            'fields' => json_encode($data),
            'user_id' => Auth::user()->id
        ]);
        $searches = Search::currentUser()->get();
        return response()->json([
            'message' => 'Поиск сохранен.',
            'fields' => $searches
            //          'view' => view('search.search_select', ['searches' => $searches])->render()
        ]);
    }

    function searchMachine(Request $request)
    {

        $data = $request->except('_token');

        SearchFilter::create([
            'name' => $request->input('name'),
            'fields' => json_encode($data),
            'user_id' => Auth::user()->id
        ]);
        $searches = SearchFilter::currentUser()->get();
        return response()->json([
            'message' => 'Поиск сохранен.',
            'fields' => $searches
//            'view' => view('search.search_select', ['searches' => $searches])->render()
        ]);
    }



    function destroy($id)
    {
        Search::currentUser()->findOrFail($id)->delete();
    }
}
