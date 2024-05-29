<?php


namespace Modules\ContractorOffice\Services;


use App\City;
use App\Helpers\RequestHelper;
use App\Http\Controllers\Avito\Models\AvitoAd;
use App\Machinery;
use App\Machines\Brand;
use App\Machines\FreeDay;
use App\Machines\MachineryModel;
use App\Machines\Type;
use App\Support\Gmap;
use App\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\CompanyOffice\Entities\Company\CompanyBranch;
use Modules\ContractorOffice\Entities\System\Tariff;
use Modules\ContractorOffice\Entities\System\TariffGrid;
use Modules\ContractorOffice\Transformers\MachineryEvent;
use Modules\ContractorOffice\Transformers\MachineryLeadEvent;
use Modules\Dispatcher\Entities\Directories\Contractor;
use Modules\Dispatcher\Entities\LeadPosition;
use Modules\Integrations\Entities\WialonVehicle;
use Modules\Orders\Entities\TechnicalWorkPlan;


class VehicleService
{

    private $owner, $data, $vehicleCategory;

    public function __construct(CompanyBranch $owner)
    {
        $this->owner = $owner;
    }

    function setData($data)
    {
        $this->vehicleCategory = Type::query()->findOrFail($data['category_id']);

        $city = City::query()->findOrFail($data['city_id']);

        $this->data = [
            'machine_type' => $this->vehicleCategory->type,
            'address' => $data['address'],
            'avito_id' => $data['avito_id'] ?? null,
            'avito_ids' => $data['avito_ids'] ?? null,
            'contractor_id' => $data['contractor_id'] ?? null,
            'delivery_radius' => $data['delivery_radius'],
            'region_id' => $data['region_id'],
            'city_id' => $data['city_id'],
            'base_id' => $data['base_id'] ?? null,
            'default_base_id' => $data['default_base_id'] ?? null,
            'board_number' => $data['board_number'] ?? null,
            'year' => $data['year'] ?? null,
            'plans' => $data['plans'] ?? [],
            'creator_id' => Auth::id(),

            'type' => $data['category_id'],
            'wialon_telematic' => $data['wialon_telematic'] ?? null,
            'brand_id' => $data['brand_id'] ?? null,
            'model_id' => $data['model_id'] ?? null,

            'vin' => $data['vin'] ?? '',
            'serial_number' => $data['serial_number'] ?? '',
            'optional_attributes' => $data['optional_attributes'] ?? [],
            'waypoints_price' => $data['waypoints_price'] ?? [],
            'prices' => $data['prices'] ?? [],
            'driver_prices' => $data['driver_prices'] ?? [],
            'tariff_type' => $data['tariff_type'],
            'description' => $data['description'],


            'insurance_premium_cost' => numberToPenny($data['insurance_premium_cost'] ?? 0),
            'market_price' => numberToPenny($data['market_price'] ?? 0),
            'available_for_sale' => toBool($data['available_for_sale'] ?? false),
            'rent_with_driver' => toBool($data['rent_with_driver'] ?? false),
            'selling_price' => numberToPenny($data['selling_price'] ?? 0),
            'pledge_cost' => numberToPenny($data['pledge_cost'] ?? 0),
            'market_price_currency' => $data['market_price_currency'] ?? null,

            'currency' => $data['currency'] ?? RequestHelper::requestDomain()->currency->code,

            'free_delivery_distance' => $data['free_delivery_distance'] ?? 0,
            'delivery_cost_over' => numberToPenny($data['delivery_cost_over']),

            'is_rented' => toBool($data['is_rented']),
            'is_rented_in_market' => toBool($data['is_rented_in_market'] ?? null),
            'show_market_price' => toBool($data['show_market_price'] ?? null),
            'show_company_market_price' => toBool($data['show_company_market_price'] ?? null),

            'price_includes_fas' => toBool($data['price_includes_fas']),
            'is_contractual_delivery' => toBool($data['is_contractual_delivery']),
            'contractual_delivery_cost' => (toBool($data['is_contractual_delivery'] ?? false)
                ? (numberToPenny($data['contractual_delivery_cost'] ?? 0))
                : 0),
            'change_hour' => $data['shift_duration'],
            'name' => $data['name'],
            'min_order_type' => $this->vehicleCategory->tariffs->isNotEmpty()
                ? 'distance'
                : ($data['min_order_type'] ?? 'shift'),
            'min_order' => $data['min_order']
                ?: 1,
            'number' => $this->vehicleCategory->type === 'machine'
                ? ($data['licence_plate'] ?? null)
                : null,
            'scans' => json_encode($data['scans']),
            'coordinates' => $data['coordinates']
                ?: Gmap::getCoordinatesByAddress($city->region->name, $city->name),
            'photo' => json_encode($data['photo'])
        ];

        return $this;
    }


    function createVehicle()
    {
        $avitoIds = $this->data['avito_ids'];
        unset($this->data['avito_ids']);

        $vehicle = new Machinery($this->data);

        $vehicle->company_branch()->associate($this->owner);

        if ($this->data['contractor_id']) {
            $contractor = Contractor::query()->forBranch($this->owner->id)->findOrFail($this->data['contractor_id']);
            $vehicle->subOwner()->associate($contractor);
        }

        $vehicle->save();

        $this->setAdditionalData($vehicle);
        $this->addAvitoIds($vehicle, $avitoIds);

        return $vehicle;

    }

    /**
     * Заполнение свзяаных данных техники
     * @param Machinery $vehicle
     * @return $this
     */
    private function setAdditionalData(Machinery $vehicle)
    {

        if (in_array($this->data['tariff_type'], [
            Tariff::DISTANCE_CALCULATION,
            Tariff::CONCRETE_MIXER,
        ])) {

            $vehicle->setDistancePrice($this->data['waypoints_price']);

        } else {

            $vehicle->setPrice($this->data['prices'], TariffGrid::WITHOUT_DRIVER);
            $vehicle->setPrice($this->data['driver_prices'], TariffGrid::WITH_DRIVER);

        }

        foreach ($this->data['plans'] as $type => $plan) {
            TechnicalWorkPlan::updateOrCreate([
                'machinery_id' => $vehicle->id,
                'type' => $type,
            ], [
                'active' => $plan['active'],
                'duration' => $plan['duration'],
                'duration_between_works' => $plan['duration_between_works'],
                'duration_plan' => $plan['duration_plan'],
            ]);
        }

        $vehicle->optional_attributes()->sync(
            $this->prepareAttributes($this->data['optional_attributes'], $this->vehicleCategory, $vehicle->model)
        );

        $this->attachWialonTelematic($vehicle);

        $vehicle->generateChpu(true);

        return $this;
    }

    function updateVehicle(
        $id,
        $forceEdit = true
    )
    {
        $avitoIds = $this->data['avito_ids'];
        unset($this->data['avito_ids']);

        $vehicle = $this->owner->machines()->findOrFail($id);

        if (!$forceEdit && $vehicle->read_only) {
            return false;
        }
        $data = $this->data;

        if ($vehicle->has_calendar) {
//            unset(
//                $data['category_id'],
//                $data['currency'],
//                $data['model_id'],
//                $data['contractor_id'],
//                $data['brand_id']
//            );
        }


        $vehicle->update($data);

        $this->setAdditionalData($vehicle);
        $this->addAvitoIds($vehicle, $avitoIds);
        return $vehicle;

    }


    function attachWialonTelematic(Machinery $machinery)
    {
        if (!empty($this->data['wialon_telematic']['id'])) {

            $wialon =
                WialonVehicle::forBranch($machinery->company_branch_id)->find($this->data['wialon_telematic']['id']);

            if ($wialon) {
                $wialon->update(['machinery_id' => $machinery->id]);
                $machinery->telematics()->associate($wialon);
                $machinery->save();
            }

        }
    }

    private function prepareAttributes(
        $attributes,
        Type $category,
        MachineryModel $model = null
    )
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


    static function getEvents(
        Machinery $machinery,
        Carbon    $dateFrom,
        Carbon    $dateTo,
                  $type = null
    )
    {
        $leads =
            LeadPosition::query()->whereHas('vehicles', function ($q) use (
                $machinery
            ) {
                $q->where('machineries.id', $machinery->id);
            })->whereHas('lead', function ($q) use (
                $dateFrom,
                $dateTo
            ) {
                $q->forPeriod($dateFrom->copy(), $dateTo->copy());
            });

        $allEvents = collect();
        $events = FreeDay::query()
            ->with('order')
            ->where('machine_id', $machinery->id)
            ->forPeriod($dateFrom->copy(), $dateTo->copy());
        switch ($type) {

            case 'service':
                $events = $events->whereHas('technicalWork');
                // $vehicleEvents = $vehicleEvents->get();
                break;
            case 'orders':
                $events = $events->whereHas('order');
                break;
            case 'leads':

                $leads = $leads->get();
                break;
            default:
                $leads = $leads->get();
        }

        $events = $events->with([
            'machine'
        ])->get();

        $allEvents = $allEvents
            ->merge($leads instanceof Builder
                ? collect()
                : MachineryLeadEvent::collection($leads))
            ->merge($events instanceof Builder
                ? collect()
                : MachineryEvent::collection($events));

        $allEvents =
            $allEvents->map(function (
                $item,
                $key
            ) {
                $item['ev_id'] = "ev-{$key}";

                return $item;
            });

        return $allEvents;
    }

    static function getAllEvents(
        array  $machineries_ids,
        Carbon $dateFrom,
        Carbon $dateTo,
               $type = null
    )
    {
        $leads = LeadPosition::query()
            ->select('*', DB::raw('DATE(date_from) as dt'));
        if ($type !== 'lead') {
            $leads = $leads->whereHas('vehicles', function ($q) use (
                $machineries_ids
            ) {
                $q->whereIn('machineries.id', $machineries_ids);
            });
        }
        $leads = $leads->whereHas('lead', function ($q) use (
            $dateFrom,
            $dateTo
        ) {
            $q->forPeriod($dateFrom->copy(), $dateTo->copy());
        });

        $allEvents = collect();
        $events = FreeDay::query()
            ->select('*', DB::raw('DATE(startDate) as dt'))
            ->with('order.leads',
                'order.customer',
                'machine.brand',
                'machine.model',
                'machine._type',
                'technicalWork',
                'order.manager')
            ->whereIn('machine_id', $machineries_ids)
            ->forPeriod($dateFrom->copy(), $dateTo->copy());

        switch ($type) {

            case 'repair':
            case 'service':
                $events = $events->whereHas('technicalWork');
                // $vehicleEvents = $vehicleEvents->get();
                break;
            case 'orders':
                $events = $events->whereHas('order');
                break;
            case 'noorders':
                $events = $events->whereDoesntHave('order');
                break;
            case 'leads':

                $leads = $leads->with(['positions', 'positions.vehicles'])->whereHas('lead', function ($q) use (
                    $dateFrom,
                    $dateTo
                ) {
                    $q->whereDoesntHave('order');
                })->get();
                break;
            default:
                $leads = $leads->get();


        }

        $events = $events->with([
            'machine'
        ])->get();

        $allEvents = $allEvents
            ->merge($events instanceof Builder
                ? collect()
                : MachineryEvent::collection($events));
        if ($type == null || $type == 'lead') {
            $allEvents = $allEvents->merge($leads instanceof Builder
                ? collect()
                : MachineryLeadEvent::collection($leads));
        }

        $allEvents =
            $allEvents->map(function (
                $item,
                $key
            ) {
                $item['ev_id'] = "ev-{$key}";

                return $item;
            });

        return $allEvents;
    }

    private function addAvitoIds(Model|Machinery $vehicle, array $avitoIds)
    {
        if (empty($avitoIds)) {
            $vehicle->avito_ads()->delete();
            return;
        }
        $avitoAds = [];

        foreach ($avitoIds as $id) {
            $avitoAds[] = new AvitoAd([
                'avito_id' => $id
            ]);
        }
        $vehicle->avito_ads()->delete();
        $vehicle->avito_ads()->saveMany($avitoAds);
    }
}
