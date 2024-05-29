<?php

namespace App\Http\Controllers\Machinery;

use App\Article;
use App\City;
use App\Machinery;
use App\Machines\Brand;
use App\Machines\Equipment;
use App\Machines\FreeDay;
use App\Machines\OptionalAttribute;
use App\Machines\SearchFilter;
use App\Machines\Type;

use App\Service\SaleService;
use App\Support\Gmap;
use App\Support\Region;
use App\User;
use Carbon\Carbon;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

use Illuminate\Support\Facades\Validator;

use Intervention\Image\ImageManagerStatic as Image;
use Rap2hpoutre\FastExcel\FastExcel;

class MachineryController extends Controller
{

    function filtersValidator($request)
    {
        $arr = [
        ];
        if ($request->filled('free') || $request->filled('order') || $request->filled('busy') || $request->filled('reserve')) {
            $arr = array_merge($arr, ['date' => 'required|date',]);
        }

        return $arr;
    }


    function index(Request $request)
    {

        $searches = SearchFilter::currentUser()->get();
        if ($request->filled('filters')) {
            $validator = Validator::make($request->all(), $this->filtersValidator($request), Machinery::getFieldsMessages());

            $errors = $validator->errors()->getMessages();

            if ($errors) return response()->json($errors, 419);
        }

        $users = User::whereHas('machines', function ($q) {
            $q->where('regional_representative_id', Auth::user()->id);
        })->get();
        $machines = Machinery::with('brand')
            ->with('_type')
            ->with('region')
            ->with('city')
            ->withFilters($request)
            ->get();
//        dd($machines);
        $regions = Region::whereCountry('russia')->get();

        return (($request->ajax())
            ? response()->json([
                'table' => view('user.machines.ajax_search', compact('machines'))->render(),
                'mobile' => view('user.machines.ajax_search_mobile', compact('machines'))->render()
            ])
            : view('user.machines.index', compact('regions', 'machines', 'searches', 'users'))
        );


    }

    public function create()
    {
        return view('user.machines.create');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        if ($request->filled('number')) {
            $request->merge(['number' => Machinery::trimNumber($request->number)]);
        }
        $validator = Validator::make($request->all(), Machinery::getRequiredFields($request), Machinery::getFieldsMessages());

        $errors = $validator->errors()->getMessages();

        if ($errors) return response()->json($errors, 419);


        DB::beginTransaction();

        $machine = Machinery::addFields((object)$request->all());
        if (!$machine) {
            $errors['modals'][] = 'Невозможно найти адрес на карте.';
        }
        if ($errors) return response()->json($errors, 419);

        if ($request->input('sticker')) {
            $machine->sticker = $request->input('sticker');
        }


        if ($request->input('photo')) {
            $machine->photo = json_encode($request->input('photo'));
        } else {
            $machine->photo = '[]';
        }

        if ($request->input('scans')) {
            $machine->scans = json_encode($request->input('scans'));
        } else {
            $machine->scans = '[]';
        }


        /*      $category = Type::find($machine->type)->name ?? '';
              $city = City::find($machine->city_id)->name ?? '';*/
        $brand = Brand::find($machine->brand_id)->name ?? '';
        $machine->save();
        $machine->alias = Article::generateChpu("{$brand} {$machine->user_id}-{$machine->id}");

        $machine->save();
        $fields = [];
        foreach ($request->all() as $key => $value) {
            $check = explode('_cat' . $machine->type . '_', $key);

            if (isset($check[0], $check[1]) && $check[0] === 'option') {
                if ($value) {
                    $fields[$check[1]] = ['value' => $value];
                }

            }
        }
        $machine->optional_attributes()->sync($fields);
        DB::commit();
        $machine->update();


        return response()->json(['message' => 'Успешно сохранено']);

    }

    public function loadImages(Request $request)
    {
        $errors = Validator::make($request->all(), [
            'files.*' => 'image|mimes:jpeg,png,jpg|max:2048',
        ])->errors()->all();

        if ($errors) return response()->json($errors, 419);
        $files = $request->file('files');
        $scans = array();
        if (!is_array($files)) {
            return response()->json([], 419);
        }
        foreach ($files as $file) {
            $extension = $file->getClientOriginalExtension();
            $fileName = str_random(5) . "-" . date('his') . "-" . str_random(3) . "." . $extension;
            $folderpath = public_path('images');
            $file->move($folderpath, $fileName);
            Image::make($folderpath . '/' . $fileName)->save($folderpath . '/' . $fileName, 50);
            $scans[] = 'images/' . $fileName;
        }

        return response()->json($scans, 201);
    }

    function showNotAvailableDays($freeDays)
    {

        $i = 0;
        $days = [];
        foreach ($freeDays as $freeDay) {
            $days[$i]['type'] = 'not';
            $days[$i]['id'] = 0;
            if ($i == 0) {
                $days[$i]['startDate'] = (string)$freeDay->startDate->subYear(3);
                $days[$i]['endDate'] = (string)$freeDay->startDate->subDay(1);
                ++$i;
                $days[$i]['startDate'] = (string)$freeDay->endDate->addDay(1);
                continue;
            }
            $days[$i]['endDate'] = (string)$freeDay->startDate->subDay(1);
            ++$i;
            $days[$i]['startDate'] = (string)$freeDay->endDate->addDay(1);
        }
        if ($freeDays->isNotEmpty()) {
            $days[$i]['type'] = 'not';
            $days[$i]['id'] = 0;
            $days[$i]['endDate'] = (string)$freeDay->endDate->addMonth(4);
        } else {
            $days[$i]['type'] = 'not';
            $days[$i]['id'] = 0;
            $days[$i]['startDate'] = (string)Carbon::now()->subYear(3);
            $days[$i]['endDate'] = (string)Carbon::now()->addMonth(4);
        }


        return $days;
    }

    /**
     * Display the specified resource.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */


    public function show(Request $request, $id)
    {
        $machine = Machinery::with('brand', '_type', 'region', 'user')
            ->where('id', $id)->firstOrFail();

        $minDate = $machine->user->created_at->format('Y-m-d');
        $maxDate = Carbon::now()->addMonth(3)->format('Y-m-d');

        $reserve = $machine->formReserve();

        $freeDays = $machine->freeDays()
            ->with('proposal.user')
            ->orderBy('startDate')
            ->get();


        //$days = $this->showNotAvailableDays($freeDays);


        $period = $freeDays->toArray();
        $period = array_merge($period, $reserve);
        return $request->ajax() ? response()->json([
            'data' => view(($request->has('show_modal'))
                ? 'machines.compact_ajax'
                : 'machines.show_ajax'
            )->with('machine', $machine)->render(),
            'period' => $period,
            'minDate' => $minDate,
            'maxDate' => $maxDate,
            //'reserve' => $reserve
        ])
            : view('machines.show')
                ->with('machine', $machine);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $machine = Machinery::with('region', 'city', 'optional_attributes')->currentUser()->findOrFail($id);

        $options = OptionalAttribute::whereTypeId($machine->type)->get();

        return view('user.machines.edit', ['machine' => $machine, 'options' => $options]);
    }

    function exportMachines()
    {
        $collection = collect([
            [
                'region' => 'Москва',
                'city' => 'Москва',
                'type' => 'Автобетононасос',
                'brand' => 'Cifa',
                'sum_hour' => '2000',
                'sum_day' => '16000',
                'change_hour' => '8',
                'number' => 'а123аа',
                'address' => 'ул. Гашека 25'
            ]
        ]);
        (new FastExcel($collection))->download('Example.xlsx', function ($coll) {

            return [
                'Регион' => $coll['region'],
                'Город' => $coll['city'],
                'Тип' => $coll['type'],
                'Марка техники' => $coll['brand'],
                'Адрес базирования' => $coll['address'],
                'Стоимость за час' => $coll['sum_hour'],
                'Стоимость за смену' => $coll['sum_day'],
                'Длительность смены' => $coll['change_hour'],
                'Гос. номер' => $coll['number'],
            ];
        });
    }

    function importMachines(Request $request)
    {
        $errors = Validator::make($request->all(), [
            'excel' => 'required|mimeTypes:' .
                'application/vnd.ms-office,' .
                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet,' .
                'application/vnd.ms-excel,csv',
        ])->errors()
            ->getMessages();


        if ($errors) return response()->json($errors, 419);

        $path = $request->file('excel')->store('excel-files');
        $machines = (new FastExcel())->import(storage_path('app/' . $path))->toArray();
        $hasErrors = [];

        $prepare_array = [
            'region',
            'city',
            'type',
            'brand',
            'address',
            'sum_hour',
            'sum_day',
            'change',
            'number'
        ];
        foreach ($machines as &$machine){
            $machine = array_values($machine);
            foreach ($machine as $key => $val){
                $machine[$prepare_array[$key]] = $val;
                unset($machine[$key]);
            }

        }
        $errors = Validator::make($machines, [
            '*.region' => 'required|exists:regions,name',
            '*.city' => 'required|exists:cities,name',
            '*.type' => 'required|exists:types,name',
            '*.brand' => 'exists:brands,name',
            '*.address' => 'required|string',
            '*.sum_hour' => 'required|numeric|min:1',
            '*.sum_day' => 'required|numeric|min:1',
            '*.change' => 'required|integer|min:1',
            '*.number' => 'required|unique:machineries,number',
        ])->errors()->getMessages();

        if($errors) {
            return response()->json($errors, 419);
        }
        DB::beginTransaction();
        foreach ($machines as $machine){

            try{

                $type = Type::whereName($machine['type'])->firstOrFail();
                $region = Region::whereName($machine['region'])->firstOrFail();
                $city = $region->cities()->whereName($machine['city'])->firstOrFail();
                $brand = Brand::whereName($machine['brand'])->firstOrFail();

            }catch (\Exception $exception){
                print_r($machine);
                return response()->json([$exception->getMessage()], 419);
            }

            $machine_data = [
                'machine_type' => $type->type,
                'address' => $machine['address'],
                'region_id' => $region->id,
                'city_id' => $city->id,
                'type' => $type->id,
                'brand_id' => $brand->id,
                'sum_hour' => round(str_replace(',', '.', $machine['sum_hour']) * 100),
                'sum_day' => round(str_replace(',', '.', $machine['sum_day']) * 100),
                'change_hour' => $machine['change'],
                'name' => "{$type->name} {$machine['number']} {$brand->name}",
                'number' => $machine['number'],
                'scans' => json_encode([]),
                'user_id' => Auth::id(),
                'coordinates' =>    Gmap::getCoordinatesByAddress($region->name, $city->name),
                'photo' => null

            ];

            $vehicle = Machinery::create($machine_data);
            $vehicle->generateChpu(true);
        }

        DB::commit();

        $errors = implode(',', $hasErrors);
        $message = $hasErrors ? "Импорт завершен. Строки {$errors} небыли загружены. Некорректный формат либо запись уже существует. Проверьте ваш файл."
            : "Импорт успешно завершен.";

        return response()->json(['message' => $message]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        if ($request->filled('number')) {
            $request->merge(['number' => Machinery::trimNumber($request->number)]);
        }
        $rules = Machinery::getRequiredFields($request);
        if ($request->machine_type === 'machine') {
            $rules['number'] = $rules['number'] . ',' . $id;
        }

        $errors = Validator::make($request->all(), $rules, Machinery::getFieldsMessages())
            ->errors()
            ->getMessages();


        if ($errors) return response()->json($errors, 419);
        /* $checkOrder = FreeDay::where('machine_id', $id)->where('type', 'order')->whereDate('endDate', '>=', Carbon::now())->first();
         if ($checkOrder) {
             return response()->json(['modals' => ['Невозможно изменить машину учавствующую в заказе']], 419);
         }*/
        DB::beginTransaction();
        $machine = Machinery::addFields((object)$request->all(), $id);
        if (!$machine) {
            return response()->json(['modals' => ['Невозможно найти указаный адрес на карте']], 419);
        }

        if ($request->input('photo')) {
            $machine->photo = json_encode($request->input('photo'));
        } else {
            $machine->photo = '[]';
        }

        if ($request->input('sticker')) {
            $machine->sticker = $request->input('sticker');
        }

        if ($request->input('scans')) {
            $machine->scans = json_encode($request->input('scans'));
        } else {
            $machine->scans = json_encode([]);
        }
        /*  $category = Type::find($machine->type)->name ?? '';
          $city = City::find($machine->city_id)->name ?? '';*/
        $brand = Brand::find($machine->brand_id)->name ?? '';

        $machine->alias = Article::generateChpu("{$brand} {$machine->user_id}-{$machine->id}");

        $machine->save();
        $fields = [];
        foreach ($request->all() as $key => $value) {
            $check = explode('_cat' . $machine->type . '_', $key);

            if (isset($check[0], $check[1]) && $check[0] === 'option') {
                if ($value) {
                    $fields[$check[1]] = ['value' => $value];
                }

            }
        }
        $machine->optional_attributes()->sync($fields);
        DB::commit();
        return response()->json(['message' => 'Успешно сохранено']);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $machine = Machinery::currentUser()->findOrFail($id);
        $checkOrder = FreeDay::where('machine_id', $id)->where('type', 'order')->first();
        if ($checkOrder) {
            return response()->json(['modals' => ['Невозможно удалить технику учавствующую в заказах']], 419);
        }
        $machine->offers()->detach();
        $machine->offers()->delete();
        $machine->freeDays()->delete();
        $machine->delete();

        return response()->json(['message' => 'Успешно удалено']);
    }

    public function checkNumber(Request $request, $id = null)
    {
        $errors = Validator::make($request->all(), [
            'number' => 'required|string'
        ])->errors()->all();

        if ($errors) return response()->json($errors, 419);
        $number = trimLicencePlate($request->input('number'));
        $machine = Machinery::where('number', $number);
        if ($id) {
            $machine->where('id', '!=', $id);
        }
        if ($machine = $machine->first()) {
            return response()->json(['owner_id' => $machine->user_id], 400);
        }
        return response()->json([], 200);
    }


    function showRent($category, $region, $city, $alias)
    {
        $category = Type::whereAlias($category)->firstOrFail();

        $region = Region::whereAlias($region)->firstOrFail();
        // dd($region->id);
        $city = City::whereAlias($city)->whereRegionId($region->id)->firstOrFail();

        $machine = Machinery::whereCityId($city->id)->whereType($category->id)->whereAlias($alias)->firstOrFail();
        $time_type = Machinery::getTimeType();
        return  response()->json();//view('user.machines.rent_page', compact('machine', 'time_type'));
    }

    function getOptionFields(Request $request)
    {
        $id = $request->type_id;

        $options = OptionalAttribute::whereTypeId($id)->get();
        $equipments = Equipment::whereTypeId($id)->get();
        return response()->json([
            'options' => view('user.machines.optional_fields', compact('options', 'id'))->render(),
            'equipments' => view('user.machines.equipment_fields', compact('equipments', 'id'))->render(),
        ]);
    }

    function salePublish(Request $request, $id)
    {
        if ($request->filled('sale_price')) {
            $request->merge([
                'sale_price' => str_replace(' ', '', $request->sale_price)
            ]);
        }
        if ($request->filled('advert_price')) {
            $request->merge([
                'advert_price' => str_replace(' ', '', $request->advert_price)
            ]);

        }
        if ($request->filled('auction_price')) {
            $request->merge([
                'auction_price' => str_replace(' ', '', $request->auction_price)
            ]);

        }
        $machine = Machinery::currentUser()->findOrFail($id);
        $rules = [
            'checks.*' => 'required|in:advert_sale,all_sale,auction_sale',
            //'spot_price' => 'required|numeric|min:1',
            'description' => 'required|string|min:10',
            // 'price' => 'required|numeric|min:1',
            'auction_type' => 'required|in:up,down',

        ];

        $checks = $request->checks ?: [];
        if (in_array('all_sale', $checks)) {
            $rules = array_merge($rules, [
                'sale_price' => 'required|numeric|min:1',
            ]);
        }
        if (in_array('advert_sale', $checks)) {
            $rules = array_merge($rules, [
                'advert_price' => 'required|numeric|min:1',
            ]);
        }
        if (in_array('auction_sale', $checks)) {
            $rules = array_merge($rules, [
                'auction_price' => 'required|numeric|min:1',
            ]);
        }


        $validator = Validator::make($request->all(), $rules);

        $errors = $validator->setAttributeNames([
            'spot_price' => trans('transbaza_machine_edit.spot_price'),
            'price' => trans('transbaza_machine_edit.price'),
            'description' => trans('transbaza_machine_edit.description'),
        ])->errors()->getMessages();

        if ($errors) return response()->json($errors, 419);


        $sale = new SaleService($request->sale_price, $request->advert_price, $request->auction_price, $request->description, $machine);

        $response = ['message' => 'Объявление опубликовано.'];

        if (in_array('all_sale', $checks)) {
            $sale->publish_sale();
        }
        if (in_array('advert_sale', $checks)) {
            $sale->publishAdvertSale();
            $response['advert_url'] = $sale->getAdvertUrl();
        }
        if (in_array('auction_sale', $checks)) {
            $sale->publishAuction();
            $response['auction_url'] = $sale->getActiontUrl();
        }

        return response()->json($response);
    }


}
