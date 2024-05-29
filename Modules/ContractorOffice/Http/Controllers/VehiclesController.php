<?php

namespace Modules\ContractorOffice\Http\Controllers;

use App\Helpers\RequestHelper;
use App\Imports\MachineryImport;
use App\Machinery;
use App\Machines\Brand;
use App\Machines\MachineryModel;
use App\Machines\OptionalAttribute;
use App\Machines\Type;
use App\Service\RequestBranch;
use App\Support\Gmap;
use App\Support\Region;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Maatwebsite\Excel\Excel;
use Modules\AdminOffice\Entities\Filter;
use Modules\CompanyOffice\Services\CompanyRoles;
use Modules\ContractorOffice\Entities\System\Tariff;
use Modules\ContractorOffice\Entities\System\TariffGrid;
use Modules\ContractorOffice\Entities\Vehicle\Price;
use Modules\ContractorOffice\Http\Requests\CreateVehicle;
use Modules\ContractorOffice\Services\Tariffs\TimeCalculation;
use Modules\ContractorOffice\Services\VehicleService;
use Modules\ContractorOffice\Transformers\Vehicle;
use Modules\ContractorOffice\Transformers\VehiclesCollection;
use Modules\Dispatcher\Entities\Customer;
use Modules\Dispatcher\Entities\Lead;
use Modules\Dispatcher\Entities\LeadPosition;
use Modules\Dispatcher\Entities\PreLeadPosition;
use Modules\Dispatcher\Services\LeadService;
use Modules\Integrations\Rules\Coordinates;
use Modules\Integrations\Services\Appraiser\AppraiserService;
use Modules\Orders\Entities\MachineryStamp;
use Modules\Orders\Entities\Order;
use Modules\Orders\Entities\OrderComponent;
use Rap2hpoutre\FastExcel\FastExcel;

class VehiclesController extends Controller
{

    private $companyBranch;

    public function __construct(Request $request, RequestBranch $companyBranch)
    {
        $this->companyBranch = $companyBranch->companyBranch;
        if (!$this->companyBranch &&  Str::contains($request->route()->getActionName(), 'getModels')) {
            return;
        }

        $block = $this->companyBranch->getBlockName(CompanyRoles::BRANCH_PROPOSALS);

        $this->middleware("accessCheck:{$block},".CompanyRoles::ACTION_SHOW)->only('getVehicles', 'getVehicleLeads',
            'getVehicleOrders', 'getAssessments', 'searchVehicles');
        $this->middleware("accessCheck:{$block},".CompanyRoles::ACTION_CREATE)->only([
            'addVehicle', 'updateVehicle', 'import', 'setRentedStatus', 'postTariff', 'setDeliveryPrices', 'updateDocuments'
        ]);
        $this->middleware("accessCheck:{$block},".CompanyRoles::ACTION_DELETE)->only(['deleteVehicle', 'deleteDocuments']);


    }

    function getOptionalAttributes(Request $request)
    {
        $options = OptionalAttribute::query();


        $options->whereTypeId($request->category_id);


        return $options->get();
    }

    function getTariffs(Request $request)
    {
        $defaultTariff = Tariff::query()->where('type', Tariff::TIME_CALCULATION)->first();

        $tariffs =
            Tariff::query()->whereHas('categories', function ($q) use (
                $request
            ) {
                $q->where('types.id', $request->input('category_id'));
            })->get();

        return $tariffs->push($defaultTariff);

    }


    function filterVehilces(Builder $vehicles)
    {
        $filter = new Filter($vehicles);
        $filter->getEqual([
            'category_id' => 'type',
            'brand_id' => 'brand_id',
            'region_id' => 'region_id',
            'city_id' => 'city_id',
            'base_id' => 'base_id',
            'default_base_id' => 'default_base_id',
            'type' => 'machine_type',
            'model_id' => 'model_id',
            'contractor_id' => 'sub_owner_id',
        ])->getLike([
            'name' => 'name'
        ]);

        if(\request()->input('avito_ids', []) && is_array(request()->input('avito_ids', ))) {
            $vehicles->whereHas('avito_ads', fn($builder) => $builder->whereIn('avito_id', request()->input('avito_ids', [])));
        }

        if (\request()->filled('status')) {
            switch (\request('status')) {
                case 'free':
                    $vehicles->whereDoesntHave('freeDays', function (Builder $q) {
                        $q->forPeriod(now(), now()->endOfDay(), false);
                    });
                    break;
                case 'repair':
                    $vehicles->whereHas('freeDays', function (Builder $q) {
                        $q->forPeriod(now()->startOfDay(), now()->endOfDay(), false)->whereHas('technicalWork');
                    });
                    break;
                case 'order':
                    $vehicles->whereHas('freeDays', function (Builder $q) {
                        $q->forPeriod(now(), now()->endOfDay(), false)->whereHas('order');
                    });
                    break;
            }
        }

        if (request()->filled('contractorMachineries')) {
            $vehicles->whereNotNull('sub_owner_id');

        } else {
            $vehicles->whereNull('sub_owner_id');
        }
        if (request()->filled('driver_type')) {

            $vehicles->whereHas('_type', function (Builder $q) {
                $q->where('types.rent_with_driver', request()->input('driver_type') === TariffGrid::WITH_DRIVER);
            });
        }

        if (toBool(request()->input('available_for_sale'))) {
            $vehicles->where('available_for_sale', true);
        }

        if (toBool(request()->input('is_rented'))) {
            $vehicles->where('is_rented', true);
        }

        if($address = request()->input('address')) {
            $vehicles->whereHas('orders', function($q) use ($address){
               $q->where('address', 'like', "%{$address}%")->whereHas('components', function ($q) {
                   $table = $q->getModel()->getTable();
                   $q->forPeriod(now(), now(), true)->whereRaw("`{$table}`.`worker_id` = `machineries`.`id`");
               });
            });
        }

        if (\request()->filled('last_service_date')) {
            $vehicles->whereHas('freeDays', function (Builder $q) {
                $q->whereDate('endDate', '<=',
                    Carbon::parse(request('last_service_date'))->setTimezone(config('app.timezone')))->whereHas('technicalWork');
            })->whereDoesntHave('freeDays', function (Builder $q) {
                $q->whereDate('endDate', '>',
                    Carbon::parse(request('last_service_date'))->setTimezone(config('app.timezone')))->whereHas('technicalWork');
            });
        }
        if(request()->filled('optional_attributes')) {
            $vehicles->whereHas('optional_attributes', function (Builder $q){

                foreach (request()->optional_attributes as $attribute){
                    [$attributeId, $value] = explode(',', $attribute);

                    $q->where(function (Builder $q) use ($attributeId, $value) {
                        $q->where($q->qualifyColumn('id'), $attributeId)
                            //->where($q->qualifyColumn('field_type'), 2)
                            ->whereRaw('CAST(attribute_machine.value AS DOUBLE) >= ?', $value);
                    });
                }


            });
        }

        $vehicles->where('read_only', request()->filled('sale'));

        if (\request()->filled('date')) {
            $date = Carbon::parse(\request('date'));
            $vehicles->checkAvailable($date, $date);
        }
    }

    public function getScaffoldings(Request $request)
    {
        return Vehicle::collection(Machinery::forBranch()
            ->where('base_id', $request->base_id)
            ->where('machine_type', 'scaffolding')
            ->get());
    }

    function getVehicles(
        Request $request,
        $id = null
    ) {
        /** @var Builder $vehicles */
        $vehicles = Machinery::forBranch()->with([
            'base',
            'brand',
            'model',
            'prices',
            'deliveryPrices',
            'waypoints_price',
            'wialon_telematic',
            '_type',
            'defaultBase',
            'region',
            'city',
            'tariff',
            'company_branch',
            'subOwner',
            'lastService',
            'futureService',
        ])->forDomain();

        $vehicles->withSum(['order_position as orders_sum' => fn(Builder $q) => $q->where('status', Order::STATUS_DONE)], 'amount');
        $vehicles->withCount(['order_position as orders_count' => fn(Builder $q) => $q->select(DB::raw('count(distinct(order_id))'))
            ->where('status', Order::STATUS_DONE)]);

        if ($id) {
            $vehicles->with([ 'optional_attributes', 'deliveryPrices.grid_prices', 'prices.gridPrices']);
            $vehicle = $vehicles->findOrFail($id);
            return Vehicle::make($vehicle);
        }
        $this->filterVehilces($vehicles);

        $paginator =  VehiclesCollection::collection($vehicles

            ->orderBy('name')->paginate($request->per_page
            ?: 5));

        $collection = $paginator->getCollection()->when($request->date('date_from') && $request->date('date_to'),function (\Illuminate\Support\Collection $collection) use($request) {
            return $collection->map(function ($vehicle) use ($request) {
                $df = $request->date('date_from');
                $dt = $request->date('date_to');
                $vehicle->is_free = !!$vehicle->getDatesForOrder($df, $df->diffInDays($dt) ?: 1, TimeCalculation::TIME_TYPE_SHIFT);
                return $vehicle;
            });
        });

        $paginator->setCollection($collection);
        return  $paginator;
    }


    private function mapMachinery($machine)
    {
        return Vehicle::make($machine);
    }

    function prepareAttributes(
        $attributes,
        Type $category
    ) {
        $arr = [];
        foreach ($attributes as $id => $attribute) {
            if (!mb_strlen($attribute) || !$category->optional_attributes()->find($id)) {
                continue;
            }
            $arr[$id] = ['value' => $attribute];
        }
        return $arr;
    }


    function importVehicles(Request $request)
    {
        try {
            DB::transaction(fn() => (new MachineryImport($this->companyBranch))->import($request->file('excel'), null, Excel::XLSX) );

        }catch (\Exception $exception) {
            logger(
                $exception->getMessage() .' '.$exception->getTraceAsString()
            );
            throw ValidationException::withMessages([
                'errors' => ["Ошибка импорта. {$exception->getMessage()}"]
            ]);
        }
    }

    function addVehicle(CreateVehicle $request)
    {
        DB::beginTransaction();

        $request->validated();

        $service = new VehicleService($this->companyBranch);
        try {

            $machine = $service->setData($request->all())->createVehicle();


            if ($request->input('telematics_type') === 'trekerserver') {
                if (!$machine->attachTrekerServerTelematic()) {
                    DB::rollBack();
                    return \response()->json(['vin' => ['Не найден в базе Trekerserver.ru']], 400);
                };
            }

        } catch (\Exception $exception) {
            DB::rollBack();
            Log::error($exception->getMessage()." ".$exception->getTraceAsString());

            return response()->json([$exception->getMessage()], 400);
        }
        DB::commit();
        return response()->json($this->mapMachinery($machine));
    }


    function updateVehicle(
        CreateVehicle $request,
        $id
    ) {
        $request->validated();

        $service = new VehicleService($this->companyBranch);


        try {
            DB::beginTransaction();

            $machine = $service->setData($request->all())->updateVehicle($id, false);
            if (!$machine) {

                return response()->json([
                    'errors' => ['Техника недоступна для редактирования']
                ], 400);
            }
            if ($request->input('telematics_type') === 'none') {
                $machine->detachTelematics();
            }

            if ($request->input('telematics_type') === 'trekerserver') {
                if (!$machine->attachTrekerServerTelematic()) {
                    DB::rollBack();
                    return \response()->json(['vin' => ['Не найден в базе Trekerserver.ru']], 400);
                };
            }

            DB::commit();
        } catch (\Exception $exception) {
            Log::error($exception->getMessage()." ".$exception->getTraceAsString());
            return response()->json([$exception->getMessage()], 400);
        }


        return response()->json($this->mapMachinery($machine));
    }

    private function getCoordinates(
        $regionName,
        $city
    ) {

        return Gmap::getCoordinatesByAddress($regionName, $city);

    }

    function deleteVehicle($id)
    {
        $machine = Machinery::forBranch()->findOrFail($id);

        $checkOrder = $machine->freeDays()->whereIn('type', ['order', 'hold'])->first();

        $order = $machine->orders->first();

        if ($checkOrder || $order) {
            return response()->json(['errors' => ['Невозможно удалить технику учавствующую в заказах.']], 400);
        }
        DB::beginTransaction();

        //$machine->offers()->detach();

        $machine->freeDays()->delete();

        $machine->audits()->delete();
        $machine->forceDelete();


        DB::commit();


        return response()->json(['message' => 'Успешно удалено']);
    }

    function getVehiclesFilters()
    {
        $categories = Type::whereHas('machines', function ($q) {
            $q->forBranch();
        })->get();

        $brands = Brand::whereHas('machines', function ($q) {
            $q->forBranch();
        })->get();

        $regions = Region::whereHas('machines', function ($q) {
            $q->forBranch();
        })->with([
            'cities' => function ($q) {
                $q->whereHas('machines', function ($q) {
                    $q->forBranch();
                });
            }
        ])->get();

        return response()->json([
            'regions' => $regions,
            'categories' => Type::setLocaleNames($categories),
            'brands' => $brands,
        ])->setExpires(now()->addMinutes(15));

    }

    function getOrdersFilters()
    {
        $categories = Type::whereHas('machines', function ($q) {
            $q->whereHas('orders')
                ->forBranch();
        })->get();

        $regions = Region::whereHas('machines', function ($q) {
            $q->whereHas('orders')
                ->forBranch();
        })->with([
            'cities' => function ($q) {
                $q->whereHas('machines', function ($q) {
                    $q->whereHas('orders')
                        ->forBranch();
                });
            }
        ])->get();

        $customers = Customer::query()->forBranch()->whereHas('orders')->get();

        return response()->json([
            'regions' => $regions,
            'categories' => Type::setLocaleNames($categories),
            'customers' => $customers,
        ]);
    }

    function setRentedStatus(
        Request $request,
        $id
    ) {
        $request->validate([
            'type' => 'required|in:is_rented,is_rented_in_market'
        ]);
        $vehicle = Machinery::forBranch()->findOrFail($id);

        $vehicle->update([
            $request->input('type') => toBool($request->input('is_rented'))
        ]);

        return response()->json();
    }

    function getModels(Request $request)
    {

        $models = MachineryModel::query()
            ->with(['characteristics', 'category.services' => function($q){
                 $q->forBranch();
            }]);
        if ($request->filled('search_word') && mb_strlen($request->input('search_word')) > 2) {
            return $models->where('name', 'like', "%{$request->input('search_word')}%")->get();
        }
        if ($request->anyFilled(['category_id', 'forBranch'])) {

            if ($request->filled('brand_id')) {

                $models->where('brand_id', $request->input('brand_id'));

            }
            if ($request->filled('category_id')) {

                $models->where('category_id', $request->input('category_id'));

            }
            if ($request->filled('model_id')) {
                $models->where('id', $request->input('model_id'));
            }
            if ($request->filled('forBranch')) {

                $models->whereHas('machines', function (Builder $q) use (
                    $request
                ) {
                    $q->forBranch();
                    if ($request->filled('subContractor')) {
                        $q->whereNotNull('sub_owner_id');
                    }
                    if (toBool($request->input('has_orders'))) {
                        if ($request->filled('lead_id')) {
                            $types = LeadPosition::query()->where('lead_id',
                                $request->input('lead_id'))->pluck('type_id')->toArray();
                            $q->whereIn('type', $types);
                        }
                        if ($request->filled('pre_lead_id')) {
                            $types = PreLeadPosition::query()->where('pre_lead_id',
                                $request->input('pre_lead_id'))->pluck('category_id')->toArray();
                            $q->whereIn('type', $types);
                        }
                        if (!$request->anyFilled([
                            'lead_id',
                            'pre_lead_id',
                        ])) {
                            $q->whereHas('order_position');
                        }
                        //
                    }
                })->with('category');

                return $models->orderBy('name')->get()->map(function ($item) {

                    $machine = $item->machines()->forBranch()->first();
                    $item->category->localization();
                    $item->min_order = $machine->min_order;
                    $item->currency = $machine->currency;
                    $item->min_order_type = $machine->min_order_type;
                    $item->cost_per_day = $machine->sum_day;
                    $item->cost_per_hour = $machine->sum_hour;
                    $item->services = $item->category->services;
                    $item->contractual_delivery_cost = $machine->contractual_delivery_cost;

                    return $item;
                });
            }

            return $models->orderBy('name')->get();
        }

        return response()->json();
    }


    function getVehicleLeads(
        Request $request,
        $id
    ) {
        $leads =
            Lead::query()->whereHas('orders', function ($q) use (
                $id
            ) {
                $q->whereHas('vehicles', function ($q) use (
                    $id
                ) {
                    $q->where('machineries.id', $id);
                });
            })->orderBy('created_at', 'desc');

        return $leads->get();
    }

    function getVehicleOrders(
        Request $request,
        $id
    ) {
        $orders =
            Order::query()->with('workers')->whereHas('vehicles', function ($q) use (
                $id
            ) {
                $q->where('machineries.id', $id);
            })->orderBy('created_at', 'desc');

        return $orders->get();
    }

    function import(Request $request)
    {
        $request->validate([
            'excel' => 'required|mimeTypes:'.
                'application/vnd.ms-office,'.
                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet,'.
                'application/vnd.ms-excel',
        ]);
        $customers = (new FastExcel())->import($request->file('excel'));
        $hasErrors = [];

        DB::beginTransaction();
        foreach ($customers as $key => $row) {

            $fields = [];
            $row = array_values($row);

            $fields['category_name'] = $row[0];

            $fields['brand_name'] = $row[1];

            $fields['model_name'] = $row[2];

            $fields['serial_number'] = $row[3];

            $number = $row[4];

            $fields['name'] = $row[5];

            $fields['address'] = $row[6];

            $fields['shift_duration'] = $row[7];
            $fields['min_order_type'] = $row[8];
            $fields['min_order'] = $row[9];

            $rules = [
                'phone' => [
                    'required',
                    'digits:'.RequestHelper::requestDomain()->options['phone_digits'],
                    Rule::unique('dispatcher_customers')->where('user_id', Auth::id())
                ],
                'email' => [
                    'required',
                    'string',
                    'email',
                    'max:255',
                    Rule::unique('dispatcher_customers')->where('user_id', Auth::id())
                ],
                'address' => 'nullable|string|max:255',
                'company' => 'required|string|max:255',
                'name' => 'required|string|max:255',
            ];


            $errors = Validator::make([
                'email' => $email,
                'phone' => $phone,
                'address' => $address,
                'company' => $company,
                'name' => $name,
            ], $rules)->errors()->all();

            if ($errors) {


                if ($email || $phone || $company) {
                    \Log::info(implode(' ', $errors));
                    $hasErrors[] = $key + 2;
                }

                continue;
            }

            $region_db = Region::query()->where('name', $region)->first();
            $city_db = false;
            if ($region_db) {
                $city_db = $region_db->cities()->where('name', $city)->first();
            }

            Customer::create([
                'company_name' => $company,
                'email' => $email,
                'phone' => $phone,
                'contact_person' => $name,
                'address' => $name,
                'region_id' => $region_db
                    ? $region_db->id
                    : null,
                'city_id' => $city_db
                    ? $city_db->id
                    : null,
                'creator_id' => Auth::id(),
                'domain_id' => RequestHelper::requestDomain()->id,
                'company_branch_id' => $this->companyBranch->id
            ]);
        }
        DB::commit();
        $errors = implode(',', $hasErrors);
        $message =
            $hasErrors
                ? "Импорт завершен. Строки {$errors} небыли загружены. Некорректный формат либо запись уже существует. Проверьте ваш файл."
                : "Импорт успешно завершен.";

        return response()->json(['message' => $message]);

    }

    function searchVehicles(Request $request)
    {
        $request->validate([
            'search_word' => 'nullable|min:3|string'
        ]);
        $vehicles = Machinery::forBranch()->with('base', '_type');

        if ($request->filled('id')) {
            $vehicles->whereId($request->input('id'));
        } else {
            $vehicles->where(function (Builder $vehicles) use (
                $request
            ) {
                $vehicles->where('name', 'like', "%{$request->input('search_word')}%");
                $vehicles->orWhereHas('base', function ($q) use (
                    $request
                ) {
                    $q->where('machinery_bases.name', 'like', "%{$request->input('search_word')}%");

                });
            });

        }
        $vehicles = $vehicles->get();
        $vehicles = $vehicles->map(function ($vehicle) {
            $name = $vehicle->name;
            if ($vehicle->base) {
                $name .= " ({$vehicle->base->name})";
            }
            return [
                'id' => $vehicle->id,
                'name' => $name,
                'category' => $vehicle->_type,
                'work_hours' => $this->companyBranch->schedule->loadMissing('workHours'),
            ];
        })->sortBy('name')->values();

        return $vehicles;
    }

    function getAssessments(
        Request $request,
        $id
    ) {
        $machinery = Machinery::forBranch()->findOrFail($id);

        $service = new AppraiserService($this->companyBranch);

        return response()->json($service->getMachineryAssesments($machinery->id));
    }

    function getActualCost(
        Request $request,
        $id
    ) {
        $request->validate([
            'order_type' => 'required|in:hour,shift',
            'order_duration' => 'required|numeric|min:1',
            'pay_type' => 'required|in:'.implode(',', Price::getTypes()),
            'type' => 'required|in:'.TariffGrid::WITHOUT_DRIVER.','.TariffGrid::WITH_DRIVER,
        ]);

        $machinery = Machinery::forBranch()->findOrFail($id);

        $cost = $machinery->calculateCost($request->input('order_type'), $request->input('order_duration'),
            $request->input('pay_type'), $request->input('type'));

        return response()->json([
            'cost' => $cost['price'],
            'value_added' => $cost['value_added'],
        ]);
    }

    function getActualDeliveryCost(
        Request $request,
        $id
    ) {
        $request->validate([
            'coordinates' => [
                'required',
                new Coordinates()
            ],
            'pay_type' => 'required|in:'.implode(',', Price::getTypes()),
            'type' => 'required|in:back,forward,forward_back,no_delivery',
        ]);

        /** @var Machinery $machinery */
        $machinery = Machinery::forBranch()->findOrFail($id);
        $distance = round($machinery->calculateDeliveryDistance($request->input('coordinates')));

        if ($request->input('type') === 'forward_back') {

            $delivery_cost = $machinery->calculateDeliveryCost($distance, 'forward', $request->input('pay_type'));
            $return_delivery = $machinery->calculateDeliveryCost($distance, 'back', $request->input('pay_type'));

            return response()->json([
                'delivery_cost' => $delivery_cost,
                'return_delivery' => $return_delivery,
                'distance' => $distance
            ]);
        }

        $cost = $machinery->calculateDeliveryCost($distance, $request->input('type'), $request->input('pay_type'));

        return response()->json(['cost' => $cost, 'distance' => $distance]);
    }

    function postTariff(
        Request $request,
        $id
    ) {
        $validate = new CreateVehicle();
        $request->validate([] + $validate->getPriceGridValidation('prices') + $validate->getPriceGridValidation('driver_prices')
        );

        /** @var Machinery $vehicle */
        $vehicle = Machinery::query()->forBranch()->findOrFail($id);


        try {
            DB::beginTransaction();

            $vehicle->update([
                'rent_with_driver' => toBool($request->input('rent_with_driver'))
            ]);
            $vehicle->setPrice($request->input('prices'), TariffGrid::WITHOUT_DRIVER);
            $vehicle->setPrice($request->input('driver_prices'), TariffGrid::WITH_DRIVER);
            DB::commit();
        } catch (\Exception $exception) {

        }

        return response()->json();
    }

    function setDeliveryPrices(
        Request $request,
        $id
    ) {
        $request->validate(CreateVehicle::getDeliveryGridValidation('delivery_forward') + CreateVehicle::getDeliveryGridValidation('delivery_back'));

        /** @var Machinery $vehicle */
        $vehicle = Machinery::query()->forBranch()->findOrFail($id);


        try {
            DB::beginTransaction();


            $vehicle->setDeliveryPrices($request->input('delivery_forward'), 'forward');

            $vehicle->setDeliveryPrices($request->input('delivery_back'), 'back');
            DB::commit();
        } catch (\Exception $exception) {

            logger($exception->getMessage().'  '.$exception->getTraceAsString());
        }

        return response()->json();


    }

    function getAvailableDates(
        Request $request,
        $id
    ) {
        $request->validate([
            'duration' => 'required|numeric|min:1',
            'date_from' => 'required|date',
            'order_type' => 'nullable|in:shift,hour',
            'worker_type' => 'nullable',
            'start_time' => 'nullable',
        ]);
        if ($request->filled('worker_type') && $request->input('worker_type') == 'warehouse_part_set') {
            $dates = [];
            if($request->input('order_type', TimeCalculation::TIME_TYPE_SHIFT)){
                $dates[] =  Carbon::parse($request->input('date_from'))->addDays($request->input('duration'))->format('Y-m-d H:i:s');
            }elseif($request->input('order_type', TimeCalculation::TIME_TYPE_HOUR)){
                $dates[] = Carbon::parse($request->input('date_from'))->addHours($request->input('duration'))->format('Y-m-d H:i:s');
            }
            return response()->json($dates);
        }
        /** @var Machinery $vehicle */
        $vehicle = Machinery::query()->forBranch()->findOrFail($id);
        $startDate = Carbon::parse($request->input('date_from'));
        if($request->input('start_time')) {
            $startDate = Carbon::parse($request->input('date_from') . ' '. $request->input('start_time'));
        }
        $dates = $vehicle->getDatesForOrder(
            $startDate,
            $request->input('duration'),
            $request->input('order_type', TimeCalculation::TIME_TYPE_SHIFT),
            null,
            $request->filled('ignoreApplication')
                ? [$request->input('ignoreApplication')]
                : [],
            $request->input('shift_duration'),
            startTime: $request->input('start_time')

        );

        return response()->json($dates);
    }

    function getAvailableDuration(
        Request $request,
        $id
    ) {
        $request->validate([
            'date_from' => 'required|date',
            'date_to' => 'required|date|after_or_equal:date_from',
            'worker_type' => 'nullable',
            'order_type' => 'nullable'
        ]);
        if ($request->filled('worker_type') && $request->input('worker_type') == 'warehouse_part_set') {
            $duration = 0;
            if($request->input('order_type', TimeCalculation::TIME_TYPE_SHIFT)){
                $duration =  Carbon::parse($request->input('date_from'))->diffInDays(Carbon::parse($request->input('date_to')));
            }elseif($request->input('order_type', TimeCalculation::TIME_TYPE_HOUR)){
                $duration = Carbon::parse($request->input('date_from'))->diffInHours(Carbon::parse($request->input('date_to')));
            }
            return response()->json([
                'duration' => $duration
            ]);
        }
        /** @var Machinery $vehicle */
        $vehicle = Machinery::query()->forBranch()->findOrFail($id);
        $duration = $vehicle->getDurationForDates(
            Carbon::parse($request->input('date_from')),
            Carbon::parse($request->input('date_to')),
            $request->filled('ignoreApplication')
                ? [$request->input('ignoreApplication')]
                : []
        );

        return response()->json(['duration' => $duration]);
    }

    function getAvailable(Request $request)
    {

        $request->validate([
            'brand_id' => 'nullable',
            'category_id' => 'nullable|exists:types,id',
            'machinery_model_id' => 'nullable',
            'machinery_base_id' => 'nullable',
            'order_type' => 'required|in:shift,hour,'.implode(',', Tariff::getTariffs()),
            'order_duration' => 'required|integer|min:1|max:500',
            'date_from' => 'required|date',
            'start_time' => 'required|date_format:H:i',
            'skip' => 'nullable|array',

        ]);
        /** @var Builder $machines */
        $machines = Machinery::with('base', '_type', 'region', 'city', 'tariff', 'company_branch', 'defaultBase', 'drivers.contacts', 'telematics',
            'model',
            'subOwner',
            'work_hours',
            'prices.gridPrices',
            'brand',
            'optional_attributes.unit_directory',
            'waypoints_price')->forBranch()
            ->sold(false)
            ->where('is_rented', true)
            ->when($request->input('skip'), function ($q) use (
                $request
            ) {
                $q->whereNotIn('id', $request->input('skip'));
            });

        if ($request->input('category_id')) {
            $machines->categoryBrandModel($request->input('category_id'), $request->input('brand_id'),
                $request->input('machinery_model_id'));

        }
        //  ->whereInCircle($lead->coords['lat'], $lead->coords['lng']);

        if($request->filled('optional_attributes')) {
            $machines->whereHas('optional_attributes', function (Builder $q) use ($request) {

                foreach ($request->optional_attributes as $attribute){
                        [$attributeId, $value] = explode(',', $attribute);

                        $q->where(function (Builder $q) use ($attributeId, $value) {
                            $q->where($q->qualifyColumn('id'), $attributeId)
                                //->where($q->qualifyColumn('field_type'), 2)
                                ->whereRaw('CAST(attribute_machine.value AS DOUBLE) >= ?', $value);
                        });
                }


            });
        }
        if ($request->filled('machinery_base_id')) {
            $machines->where('base_id', $request->input('machinery_base_id'));
        }
        if ($request->filled('board_number')) {
            $boardNumber = trim($request->input('board_number'));
            $machines->where(fn($q) => $q->where('board_number', 'like', "%$boardNumber%")->orWhere('name', 'like', "%$boardNumber%"));
        }
        $arr = [];
        $dateFrom = Carbon::parse($request->input('date_from').' '.($request->start_time ?: ''));
        $dateTo = getDateTo($dateFrom, $request->input('order_type'), $request->input('order_duration'));
//        $machines->where('min_order', '<=', $request->input('order_duration'))
//            ->when($request->input('order_type') === TimeCalculation::TIME_TYPE_SHIFT, function ($q) use ($request) {
//                $q->where('min_order_type', $request->input('order_type'));
//            });
        LeadService::getMachineriesForPeriod($machines, $dateFrom,
            $request->input('order_type'), $request->input('order_duration'), [])
            ->map(/**
             * @param  Machinery  $machine
             * @return mixed
             */ function ($machine) use (
                $dateFrom,
                $request,
                $dateTo,
                &$arr
            ) {

                $machine->in_radius = $machine->isInCircle($request->input('coordinates'));
                /** @var OrderComponent $orderComponent */
                $orderComponent = OrderComponent::query()->where('worker_id', $machine->id)->orderBy('date_to', 'desc')
                    ->where('date_to', '<=', $dateFrom)
                    ->whereNotIn('status', [Order::STATUS_REJECT])
                    ->first();

                if ($orderComponent) {
                    $exists = MachineryStamp::query()->where('order_id', $orderComponent->order_id)
                        ->where('machinery_id', $machine->id)
                        ->where('type', 'done')->exists();
                    $machine->has_act = $exists;

                    $machine->no_act_order_id = $exists ? null : $orderComponent->order_id;
                    $machine->no_act_internal_number = $exists ? null : $orderComponent->order_internal_number;
                } else {
                    $machine->has_act = true;
                }


                $firstDate = Carbon::parse($machine->order_dates[0]);
                $lastDate = Carbon::parse($machine->order_dates[count($machine->order_dates) - 1]);
                $dt = $dateTo->clone();
                if ($machine->change_hour === 24) {
                    $dt->addDay();
                }
                $sameDays = $firstDate->format('Y-m-d') === $dateFrom->format('Y-m-d')
                    && $dt->format('Y-m-d') === $lastDate->format('Y-m-d');


                if ($machine->has_act) {
                    $sort = $sameDays ? 0 : 3;
                } else {
                    $sort = $sameDays ? 1 : 2;
                }

                $machine->sort_order = $sort;
                //$machine->sum_day = $machine->getSumByPriceType(TimeCalculation::TIME_TYPE_SHIFT, ($lead->contractorRequisite ? $lead->contractorRequisite->vat_system : Price::TYPE_CASH));
                //$machine->sum_hour = $machine->getSumByPriceType(TimeCalculation::TIME_TYPE_HOUR, ($lead->contractorRequisite ? $lead->contractorRequisite->vat_system : Price::TYPE_CASH));
                $arr[] = $machine;

                return $machine;
            });

        usort($arr, function ($itemA, $itemB) {
            $a = $itemA->sort_order;
            $b = $itemB->sort_order;
            if ($a == $b) {
                return 0;
            }
            return ($a < $b) ? -1 : 1;
        });

        return response()->json($arr);
    }

    function updateDocuments(Request $request, $id)
    {
        $request->validate([
            '*.name' => 'required|string|max:255',
            '*.files' => 'required|array',
        ]);
        $vehicle = Machinery::query()->forBranch()->findOrFail($id);

        foreach ($request->all() as $doc) {
            foreach ($doc['files'] as $file) {
                $vehicle->addDocument($doc['name'], $file);
            }
        }

    }

    function getDocuments(Request $request, $id)
    {
        $vehicle = Machinery::query()->forBranch()->findOrFail($id);

        return $vehicle->documents;

    }

    function deleteDocuments(Request $request, $id)
    {
        $request->validate([
            'document_id' => 'required'
        ]);

        $vehicle = Machinery::query()->forBranch()->findOrFail($id);

        return $vehicle->documents()->where('id', $request->input('document_id'))->delete();
    }

}
