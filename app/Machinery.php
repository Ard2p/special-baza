<?php

namespace App;

use App\Ads\Advert;
use App\Http\Controllers\Avito\Models\AvitoAd;
use App\Machines\Brand;
use App\Machines\FreeDay;
use App\Machines\MachineryModel;
use App\Machines\OptionalAttribute;
use App\Machines\Sale;
use App\Machines\Type;
use App\Machines\WorkHour;
use App\Modules\MachineAuctions\Auction;
use App\Service\EventNotifications;
use App\Support\Gmap;
use App\Support\Region;
use Carbon\Carbon;
use Cassandra\Time;
use GoogleMaps\Facade\GoogleMapsFacade as GoogleMaps;
use http\Exception\InvalidArgumentException;
use Illuminate\Database\Eloquent\Builder;
use App\Overrides\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Intervention\Image\ImageManagerStatic as Image;
use Modules\AdminOffice\Entities\Filter;
use Modules\CompanyOffice\Entities\Company\CompanyBranch;
use Modules\CompanyOffice\Services\BelongsToCompanyBranch;
use Modules\CompanyOffice\Services\HasManager;
use Modules\CompanyOffice\Services\InternalNumbering;
use Modules\ContractorOffice\Entities\CompanyWorker;
use Modules\ContractorOffice\Entities\System\Tariff;
use Modules\ContractorOffice\Entities\System\TariffGrid;
use Modules\ContractorOffice\Entities\System\TariffGridPrice;
use Modules\ContractorOffice\Entities\System\TariffUnitCompare;
use Modules\ContractorOffice\Entities\Telematics\Trekerserver;
use Modules\ContractorOffice\Entities\Vehicle\DeliveryPrice;
use Modules\ContractorOffice\Entities\Vehicle\DeliveryTariffGrid;
use Modules\ContractorOffice\Entities\Vehicle\MachineryBase;
use Modules\ContractorOffice\Entities\Vehicle\MachineryDayOff;
use Modules\ContractorOffice\Entities\Vehicle\Price;
use Modules\ContractorOffice\Entities\Vehicle\Shop\MachineryPurchase;
use Modules\ContractorOffice\Entities\Vehicle\Shop\MachinerySale;
use Modules\ContractorOffice\Entities\Vehicle\Shop\OperationCharacteristic;
use Modules\ContractorOffice\Entities\Vehicle\TechnicalWork;
use Modules\ContractorOffice\Entities\Vehicle\WaypointsPrice;
use Modules\ContractorOffice\Jobs\NewVehicleNotification;
use Modules\ContractorOffice\Services\Tariffs\TimeCalculation;
use Modules\Dispatcher\Entities\Directories\Contractor;
use Modules\Dispatcher\Entities\LeadPosition;
use Modules\Integrations\Entities\WialonVehicle;
use Modules\Orders\Entities\MachineryStamp;
use Modules\Orders\Entities\Order;
use Modules\Orders\Entities\OrderComponent;
use Modules\Orders\Entities\OrderDocument;
use Modules\Orders\Entities\Service\ServiceCenter;
use Modules\Orders\Entities\TechnicalWorkPlan;
use Modules\Orders\Services\OrderTrait;
use Modules\RestApi\Entities\Currency;
use OwenIt\Auditing\Auditable;
use Stevebauman\Purify\Facades\Purify;

class Machinery extends Model implements \OwenIt\Auditing\Contracts\Auditable
{

    use Auditable, SoftDeletes, BelongsToCompanyBranch, HasManager, InternalNumbering, OrderTrait;


    /**
     * @var array
     */
    protected $auditInclude = [
        'region_id', 'scans', 'address', 'photo', 'sum_hour', 'sum_day', 'type', 'brand_id', 'psm_number', 'name',
        'manufacturer', 'certificate',
        'certificate_date', 'issued_by', 'checkup_by', 'checkup_date', 'year', 'number', 'engine', 'transmission',
        'leading_bridge', 'colour', 'integration_native_id', 'machine_type', 'currency',
        'engine_power', 'construction_weight', 'construction_speed', 'comment', 'act_number', 'act_date', 'act_year',
        'psm_manufacturer_number', 'engine_type', 'dimensions', 'coordinates', 'delivery_radius',
        'year_release', 'owner', 'basis_for_witness', 'witness_date', 'city_id', 'has_sticker', 'sticker',
        'sticker_promo_code', 'who_glued_sticker', 'characteristic', 'advert_id', 'creator_id', 'change_hour',
        'free_delivery_distance', 'is_rented', 'min_order', 'min_order_type',
        'delivery_cost_over', 'description', 'board_number',
        'is_contractual_delivery',
        'contractual_delivery_cost',
        'price_includes_fas',
        'vin', 'serial_number',
        'telematics_type',
        'tariff_type',
        'telematics_id',
        'model_id',
        'base_id',
    ];


    /**
     * @var array
     */
    protected $fillable = [

        'region_id', 'scans', 'address', 'photo', 'sum_hour', 'sum_day', 'type', 'brand_id', 'psm_number', 'name',
        'manufacturer', 'certificate', 'description',
        'certificate_date', 'issued_by', 'checkup_by', 'checkup_date', 'year', 'number', 'engine', 'transmission',
        'leading_bridge', 'colour', 'integration_native_id', 'machine_type', 'currency',
        'engine_power', 'construction_weight', 'construction_speed', 'comment', 'act_number', 'act_date', 'act_year',
        'psm_manufacturer_number', 'engine_type', 'dimensions', 'coordinates', 'delivery_radius',
        'year_release', 'owner', 'basis_for_witness', 'witness_date', 'city_id', 'has_sticker', 'sticker',
        'sticker_promo_code', 'who_glued_sticker', 'characteristic', 'advert_id', 'creator_id', 'change_hour',
        'free_delivery_distance', 'is_rented', 'min_order', 'min_order_type',
        'delivery_cost_over', 'board_number',
        'is_contractual_delivery',
        'contractual_delivery_cost',
        'price_includes_fas',
        'vin', 'serial_number',
        'telematics_type',
        'telematics_id',
        'tariff_type',
        'market_price',
        'market_price_currency',
        'is_rented_in_market',
        'rent_with_driver',
        'model_id',
        'base_id',
        'default_base_id',
        'read_only',
        'selling_price',
        'available_for_sale',
        'show_market_price',
        'show_company_market_price',
        'default_machinery_base_id',
        'pledge_cost',
        'insurance_premium_cost',
        'avito_id',
        'engine_hours_after_tw',
        'days_after_tw',
    ];

    /**
     * @var array
     */
    protected $appends = [
        'shift_duration'
     //   'full_address',
     //   'sum_day_format',
     //   'sum_hour_format',
     //   'sum_hour',
     //   'sum_day',
     //   'photos', 'currency_info', 'rent_url',
    ];

    /**
     * @var array
     */
   // protected $with = ['_type', 'region', 'city', 'company_branch'];

    /**
     * @var array
     */
    protected $casts = [
        'change_hour' => 'integer',
        'is_contractual_delivery' => 'boolean',
        'is_rented' => 'boolean',
        'price_includes_fas' => 'boolean',
        'is_rented_in_market' => 'boolean',
        'available_for_sale' => 'boolean',
        'rent_with_driver' => 'boolean',
        'show_market_price' => 'boolean',
        'show_company_market_price' => 'boolean',
        'engine_hours_after_tw' => 'float',
        'days_after_tw' => 'float',
    ];

    /**
     * @var array
     */
    static $nameAttributes = [

    ];


    /**
     * @param $v
     * @return string
     */
    static function trimNumber($v)
    {
        return trim(str_replace(' ', '', $v));
    }

    /**
     *
     */
    protected static function boot()
    {
        parent::boot();

        static::updated(function (self $model) {
            $model->generateSeoPhoto();
        });

        static::created(function (self $model) {
            $model->generateSeoPhoto();

            foreach (\App\Machines\WorkHour::$day_type as $item) {
                \App\Machines\WorkHour::create([
                    'machine_id' => $model->id,
                    'from' => \Carbon\Carbon::parse('08:00', 'Europe/Moscow'),
                    'to' => \Carbon\Carbon::parse('18:00', 'Europe/Moscow'),
                    'day_name' => $item,
                    'is_free' => 0,
                ]);
            }

            foreach (Price::getTypes() as $type) {
                $model->prices()->save(new Price([
                    'type' => $type,
                    'cost_per_hour' => 0,
                    'cost_per_shift' => 0,
                ]));
            }


            $model->waypoints_price()->save(new WaypointsPrice([]));


            dispatch(new NewVehicleNotification($model->id))->delay(now()->addSeconds(10));

            $model->setInternalNumber();

        });

        static::deleted(function (self $model) {

            $model->deletePhotos();
        });
    }

    /**
     * @param  array  $data
     * @return array
     */
    function transformAudit(array $data): array
    {
        if (Arr::has($data, 'new_values.coordinates') || Arr::has($data, 'old_values.coordinates')) {

            $data['old_values']['coordinates'] = getDbCoordinates($this->getOriginal('coordinates'));
            $data['new_values']['coordinates'] = $this->coordinates;
        }

        return $data;
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    function orderDays()
    {
        return $this->hasMany(FreeDay::class, 'machine_id')->where('free_days.type', '=', 'order');
    }


    function drivers()
    {
        return $this->belongsToMany(CompanyWorker::class, 'company_workers_machinery');
    }

    function avito_ads()
    {
        return $this->hasMany(AvitoAd::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\MorphToMany
     */
    function orders()
    {
        return $this->morphToMany(Order::class, 'worker', 'order_workers')->withPivot('amount', 'date_from', 'date_to');
    }

    function order_position()
    {
        return $this->morphMany(OrderComponent::class, 'worker')->accepted();
    }


    function subOwner()
    {
        return $this->morphTo();
    }

    /*  public function newQuery($excludeDeleted = true)
      {
          $raw = ' AsText(coordinates) as coordinates ';
          return parent::newQuery($excludeDeleted)->addSelect('*', \DB::raw($raw));
      }*/

    function setDescriptionAttribute($val)
    {
        $this->attributes['description'] = Purify::clean($val);
    }

    /**
     * @param $val
     */
    function setCoordinatesAttribute($val)
    {
        $coords = explode(',', trim($val));
        $query = "GeomFromText('POINT($coords[0] $coords[1])')";
        $this->attributes['coordinates'] = \DB::raw($query);

    }

    /**
     * @param $value
     * @return string|null
     */
    function getCoordinatesAttribute($value)
    {
        return getDbCoordinates($value);
    }

    /**
     * @param  Request  $request
     * @return array
     */
    static function getRequiredFields(Request $request)
    {


        $arr = [
            'region' => 'required|integer||exists:regions,id',
            'machine_type' => 'required|in:machine,equipment',

            'change_hour' => 'required|integer|min:1|',
            'city_id' => 'required|integer|exists:cities,id',
            'sum_hour' => 'required|numeric|min:1|',
            'sum_day' => 'required|numeric|min:1|',
            'brand_id' => 'required|integer|exists:brands,id',
            /* 'address' => 'required|string',*/
            /*'name' => 'required|string',*/
            'photo.*' => 'required|string',

        ];
        if ($request->machine_type === 'machine') {
            $arr = array_merge($arr,
                [
                    'type' => [
                        'required',
                        Rule::exists('types', 'id')->where('type', 'machine')
                    ],
                    'number' => 'required|min:4|string|unique:machineries,number'
                ]);
        } else {
            $arr = array_merge($arr, [
                'type_eq' => [
                    'required',
                    Rule::exists('types', 'id')->where('type', 'equipment')
                ]
            ]);
        }
        if ($request->filled('regional_representative_id')) {
            $arr = array_merge($arr, ['regional_representative_id' => 'exists:users,id',]);
        }
        if ($request->filled('promoter_id')) {
            $arr = array_merge($arr, ['promoter_id' => 'exists:users,id',]);
        }

        return $arr;
    }

    /**
     * @var array
     */
    static $mailFields = [
        'type_name' => 'Категория техники',
        'brand_name' => 'Бренд',
        'year_release' => 'Год выпуска',
    ];

    /**
     * @var array
     */
    static $fields = [
        'number' => 'Гос.номер',
        'region' => 'Регион',
        'type_name' => 'Категория техники',
        'brand_name' => 'Бренд',
        'brand_id' => 'Бренд',
        'psm_number' => 'Номер ПСМ',
        'sum_hour_format' => 'Стоимость в час',
        'change_hour' => 'Длительность смены, часов',
        'sum_day_format' => 'Стоимость за смену',
        'full_address' => 'Адрес',
        'year_release' => 'Год выпуска',
        'owner' => 'Владелец',
        'basis_for_witness' => 'Выдано на основании',
        'witness_date' => 'Дата свидетельства',
        'psm_manufacturer_number' => 'Заводской номер машины',
        'engine' => 'Двигатель',
        'transmission' => 'Коробка передач',
        'leading_bridge' => 'Основной ведущий мост',
        'colour' => 'Цвет',
        'engine_type' => 'Вид двигателя',
        'engine_power' => 'Мощность двигателя',
        'construction_weight' => 'Конструкционная масса',
        'construction_speed' => 'Макс. конструктивная скорость',
        'dimensions' => 'Габаритные размеры',
        'act_number' => 'Номер акта',
        'act_date' => 'Дата акта',
        'act_year' => 'Год выпуска',
        'certificate' => 'Номер сертификата соответствия',
        'certificate_date' => 'Дата сертификата',
        'issued_by' => 'Кем Выдан',
    ];

    /**
     * @var array
     */
    static public $fieldsMessages = [
        'sum_hour.required' => 'Заполните стоимость техники в час.',
        'type.required' => 'Выберите категорию',
        'type_eq.required' => 'Выберите категорию',
        'sum_hour.numeric' => 'Стоимость техники в час должно быть числом.',
        'psm_number.required' => 'Заполните номер ПСМ',
        'change_hour.integer' => 'Стоимость техники в час должно быть числом.',
        'sum_day.required' => 'Заполните стоимость техники в день.',
        'sum_day.numeric' => 'Стоимость техники в день должно быть числом.',
        'brand_id.required' => 'Выберите марку техники.',
        'brand_id.integer' => 'Выберите марку техники из списка.',
        'brand_id.exists' => 'Выберите марку техники из списка.',
        'region.required' => 'Выберите регион.',
        'city_id.required' => 'Выберите город.',
        'address.required' => 'Заполните адрес.',
        'name.required' => 'Заполните наименование.',
        'photo.required' => 'Выберите изображение.',
    ];

    /**
     * @return array
     */
    static function getFieldsMessages()
    {
        return [
            'rent_price.required' => trans('transbaza_machine_edit.validate_cost_per_hour'),
            'type.required' => trans('transbaza_machine_edit.validate_category'),
            'type_eq.required' => trans('transbaza_machine_edit.validate_category'),
            'rent_price.numeric' => trans('transbaza_machine_edit.validate_cost_number'),
            'psm_number.required' => trans('transbaza_machine_edit.validate_psm'),
            'shift_duration.required' => trans('transbaza_machine_edit.validate_cost_number'),
            'shift_rent_price.required' => trans('transbaza_machine_edit.validate_cost_per_day'),
            'shift_rent_price.numeric' => trans('transbaza_machine_edit.validate_cost_per_day_number'),
            'brand_id.required' => trans('transbaza_machine_edit.validate_brand'),
            'brand_id.integer' => trans('transbaza_machine_edit.validate_brand_from_list'),
            'brand_id.exists' => trans('transbaza_machine_edit.validate_brand_from_list'),
            'region_id.required' => trans('transbaza_machine_edit.validate_region'),
            'city_id.required' => trans('transbaza_machine_edit.validate_city'),
            'address.required' => trans('transbaza_machine_edit.validate_address'),
            'name.required' => trans('transbaza_machine_edit.validate_name'),
            'photo.required' => trans('transbaza_machine_edit.validate_image'),
        ];
    }

    /**
     * @return array
     */
    static function getTimeType()
    {
        return [
            [
                'id' => 1,
                'name' => trans('transbaza_machine_edit.time_type_hour'),
            ],
            [
                'id' => 2,
                'name' => trans('transbaza_machine_edit.time_type_change'),
            ],
            [
                'id' => 3,
                'name' => trans('transbaza_machine_edit.time_type_day'),
            ],
            [
                'id' => 4,
                'name' => trans('transbaza_machine_edit.time_type_week'),
            ],
            [
                'id' => 5,
                'name' => trans('transbaza_machine_edit.time_type_month'),
            ],
        ];
    }

    /**
     * @return array
     */
    static function getTelematics()
    {
        return [
            'wialon' => 'Wialon',
            'trekerserver' => 'TrekerServer.ru',
        ];
    }

    /**
     * @return mixed|string
     */
    function getTelematicsType()
    {
        $telematics = [
            Trekerserver::class => 'trekerserver',
            WialonVehicle::class => 'wialon',
        ];
        return $this->telematics_type
            ?
            $telematics[$this->telematics_type]
            : 'none';
    }

    /**
     * Цены для техники
     * @return \Illuminate\Database\Eloquent\Relations\MorphTo
     */
    function prices()
    {
        return $this->morphMany(TariffGrid::class, 'machinery');
    }

    function deliveryPrices()
    {
        return $this->hasMany(DeliveryTariffGrid::class)->orderBy('min');
    }

    function sales()
    {
        return $this->morphToMany(MachinerySale::class, 'owner', 'machinery_shop_characteristic', 'machinery_id');
    }

    function purchases()
    {
        return $this->morphToMany(MachineryPurchase::class, 'owner', 'machinery_shop_characteristic', 'machinery_id');
    }

    /**
     * Цены для расчета по тарифу киллометража.
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    function waypoints_price()
    {
        return $this->hasOne(WaypointsPrice::class);
    }


    /**
     * Указание цен для разного типа оплаты
     *
     * @param $prices
     * Преобразовать цену в копейки.
     * @param  bool  $toPenny
     * @return Machinery
     */
    function setPrice(
        $prices,
        $type = TariffGrid::WITHOUT_DRIVER,
        $toPenny = true
    ) {
        $ids = [];
        foreach ($prices as $price) {

            $current = TariffGrid::query()->updateOrCreate([
                'unit_compare_id' => $price['unit_compare_id'],
                'min' => $price['min'],
                'type' => $type,
                'machinery_id' => $this->id,
            ], [
                'market_markup' => $price['market_markup'] ?? 0,
                'is_fixed' => toBool($price['is_fixed']),
                'sort_order' => 0,
            ]);

            $ids[] = $current->id;

            foreach ($price['grid_prices'] as $key => $value) {

                if (!$value) {
                    $value = 0;
                }

                $gridPrice = $current->gridPrices()->where('price_type', $key)->first();

                $gridPrice
                    ? $gridPrice->update([
                    'price' => $toPenny
                        ? numberToPenny($value)
                        : $value,
                ])
                    :
                    $current->gridPrices()->save(new TariffGridPrice([
                        'price' => $toPenny
                            ? numberToPenny($value)
                            : $value,
                        'price_type' => $key,
                    ]));

            }

//            $costPerShift = $toPenny ? numberToPenny($price['cost_per_shift']) : $price['cost_per_shift'];
//            $costPerHour = $toPenny ? numberToPenny($price['cost_per_hour']) : $price['cost_per_hour'];
//
//            $this->prices()->where('type', $price['type'])->update([
//                'cost_per_shift' => $costPerShift,
//                'cost_per_hour' => $costPerHour,
//            ]);
        }
        $this->prices()->where('type', $type)->whereNotIn('id', $ids)->delete();
        return $this;
    }


    function setDeliveryPrices(
        $prices,
        $type,
        $toPenny = true
    ) {
        $ids = [];
        foreach ($prices as $price) {

            $fields = [
                'min' => $price['min'],
                'is_fixed' => toBool($price['is_fixed'] ?? false),
                'type' => $type,
                'machinery_id' => $this->id,
            ];
            if (!empty($price['id'])) {
                $current = $this->deliveryPrices()->findOrFail($price['id']);
                $current->update($fields);

            } else {

                /** @var DeliveryTariffGrid $current */
                $current = $this->deliveryPrices()->save(new DeliveryTariffGrid($fields));
            }

            $ids[] = $current->id;

            foreach ($price['grid_prices'] as $key => $value) {

                $gridPrice = $current->grid_prices()->where('price_type', $key)->first();

                $gridPrice
                    ? $gridPrice->update([
                    'price' => $toPenny
                        ? numberToPenny($value)
                        : $value,
                ])
                    :
                    $current->grid_prices()->save(new DeliveryPrice([
                        'price' => $toPenny
                            ? numberToPenny($value)
                            : $value,
                        'price_type' => $key,
                    ]));

            }
        }
        $this->deliveryPrices()->where('type', $type)->whereNotIn('id', $ids)->delete();
        return $this;
    }

    /**
     * Указание цен для расчета за пройденый путь
     *
     * @param $prices
     * Преобразовать цену в копейки.
     * @param  bool  $toPenny
     * @return Machinery
     */
    function setDistancePrice(
        array $distances,
        $toPenny = true
    ) {
        $distances =
            collect($distances)->map(function ($item) use (
                $toPenny
            ) {

                if ($toPenny) {
                    foreach (WaypointsPrice::getTypes() as $type) {
                        $item[$type] = numberToPenny($item[$type]);
                    }

                }

                return $item;
            });

        $this->waypoints_price->update([
            'distances' => $distances->sortBy('distance')
        ]);
        return $this;
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function brand()
    {
        return $this->belongsTo(Brand::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    function wialon_telematic()
    {
        return $this->hasOne(WialonVehicle::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\MorphTo
     */
    function telematics()
    {
        return $this->morphTo();
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    function tariff()
    {
        return $this->belongsTo(Tariff::class, 'tariff_type', 'type');
    }

    /**
     * @return mixed
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'creator_id')->withTrashed();
    }


    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    function model()
    {
        return $this->belongsTo(MachineryModel::class, 'model_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function _type()
    {
        return $this->belongsTo(Type::class, 'type');
    }

    function order_timestamps()
    {
        return $this->morphMany(MachineryStamp::class, 'machinery');
    }


    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function regional_representative()
    {
        return $this->hasOne(User::class, 'id', 'regional_representative_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function promoter()
    {
        return $this->hasOne(User::class, 'id', 'promoter_id')->where('users.is_promoter', '=', 1);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function region()
    {
        return $this->belongsTo(Region::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    function optional_attributes()
    {
        return $this->belongsToMany(OptionalAttribute::class,
            'attribute_machine')->withPivot('value')->orderBy('priority');
    }

    /**
     * Технические работы, ремонты и.т.п.
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    function technicalWorks()
    {
        return $this->hasMany(TechnicalWork::class);
    }

    function daysOff()
    {
        return $this->hasMany(MachineryDayOff::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function city()
    {
        return $this->belongsTo(City::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function work_hours()
    {
        return $this->hasMany(WorkHour::class, 'machine_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function freeDays()
    {
        return $this->hasMany(FreeDay::class, 'machine_id', 'id');
    }

    /**
     * @param $query
     * @param $category_id
     * @param  null  $brand_id
     * @param  null  $model_id
     * @return mixed
     */
    function scopeCategoryBrandModel(
        $query,
        $category_id,
        $brand_id = null,
        $model_id = null
    ) {
        $query->where('type', $category_id);
        if ($brand_id) {
            $query->where('brand_id', $brand_id);
        }
        if ($model_id) {
            $query->where('model_id', $model_id);
        }

        return $query;
    }

    function generateDaysOff($companySchedule = false)
    {
        $now = now()->startOfDay();
        $end = now()->endOfYear();
        DB::beginTransaction();

        FreeDay::query()->where('machine_id', $this->id)
            ->where('type', 'day_off')
            ->where('startDate', '>', now())
            ->delete();

        $schedule = $this->company_branch->schedule()->where('day_off', true)->get();

        foreach (FreeDay::generateDateRange($now, $end) as $dayOff) {

            //  dd($schedule->toArray());
            if ($companySchedule) {
                $dayOfWeek =
                    $dayOff->dayOfWeek === 0
                        ? 6
                        : $dayOff->dayOfWeek - 1;
                if (!$schedule->contains('day_of_week', $dayOfWeek) && !$this->daysOff()->whereDate('date',
                        $dayOff)->exists()) {
                    continue;
                }
            } else {
                if (!$this->work_hours()
                    ->where('day_name',
                        $dayOff->locale('en')->format('D'))->where('is_free', 1)->exists()
                ) {
                    continue;
                }
            }
            if (!$this->freeDays()->forPeriod($dayOff->copy(), $dayOff->copy())->exists()) {
                \App\Machines\FreeDay::create([
                    'startDate' => $dayOff->startOfDay(),
                    'endDate' => $dayOff->copy()->endOfDay(),
                    'type' => 'day_off',
                    'machine_id' => $this->id,
                ]);
            }


        }

        DB::commit();
    }

    function getScheduleForDay($dayName)
    {
        return $this->work_hours()->where('day_name', $dayName)->first();
    }

    function generateOrderCalendar(
        $dates,
        $orderType,
        $orderDuration,
        $orderComponent,
        Carbon $dateFrom,
        Carbon $dateTo
    ) {
        $i = 0;
        $isDayRent = $this->change_hour === 24;
        $diffInMinutes = $dateFrom->copy()->startOfDay()->diffInMinutes($dateFrom);

        foreach ($dates as $date) {
            ++$i;
            $startDate = Carbon::parse($date);
            $endDate = $startDate->copy();

            $currentDay = $this->getScheduleForDay($startDate->format('D'));

            if ($i !== 1) {
                if ($isDayRent) {
                    $startDate->startOfDay();
                } else {
                    $startDate->startOfDay()
                        ->addHours($currentDay->time_from[0])
                        ->addMinutes($currentDay->time_from[1]);
                }
            }

            if ($orderType === TimeCalculation::TIME_TYPE_HOUR) {
                $endDate = getDateTo($dateFrom, $orderType, $orderDuration);
            } else {
                if ($isDayRent) {
                    $i !== count($dates) ? $endDate->endOfDay() : $endDate->startOfDay()->addMinutes($diffInMinutes)->subMinute();
                } else {
                    $endDate->setHour($currentDay->time_to[0]);
                    $endDate->setMinute($currentDay->time_to[1]);
                }
            }
            if ($startDate->gt($endDate)) {
                $endDate->endOfDay();
            }

            if ($i === count($dates)) {
                $endDate = $dateTo;
            }
            FreeDay::create([
                'startDate' => $startDate,
                'endDate' => $endDate,
                'type' => 'order',
                'order_id' => $orderComponent->order_id,
                'order_component_id' => $orderComponent->id,
                'machine_id' => $this->id
            ]);
        }
    }

    function getDatesForOrder(
        Carbon $startDate,
        $duration,
        $type = TimeCalculation::TIME_TYPE_SHIFT,
        $maxOffset = null,
        $ignore = [],
        $shiftDuration = null,
        $startTime = null,
        $forceAddDay = false
    ) {
        $maxOffset =
            $maxOffset
                ?: $duration * 4;

        $dates = [];
        $i = 0;
        $start = $startDate->copy();
        $shiftDuration = (int) ($shiftDuration ?: $this->change_hour);
        $startDT = $startDate->copy();
        if($startTime) {
            $startDT->setTimeFrom(Carbon::parse($startTime));
        }
        if ($shiftDuration === 24 && $type === TimeCalculation::TIME_TYPE_SHIFT && !$startDT?->isStartOfDay()) {
            if($forceAddDay) {
                $duration += 1;
            }
        }

        $currentDay = $this->getScheduleForDay($start->format('D'));

        while (count($dates) < ($type === TimeCalculation::TIME_TYPE_HOUR
                ? 1
                : $duration)) {

            if ($i !== 0) {

                $startDate->addDay();

                $currentDay = $this->getScheduleForDay($start->format('D'));
                if ($type === TimeCalculation::TIME_TYPE_SHIFT) {
                    $startDate->startOfDay();

                    if($shiftDuration !== 24) {
                        $startDate->addHours($currentDay->time_from[0])
                            ->addMinutes($currentDay->time_from[1]);
                    }
                }

            }
            if ($i > $maxOffset) {
                return [];
            }

            if ($type === TimeCalculation::TIME_TYPE_SHIFT) {
                if ($shiftDuration === 24) {
                    $diff = $start->copy()->startOfDay()->diffInMinutes($start);
                    $end =
                        ($i === ($duration - 2)
                            ? $startDate->copy()->addDay()->addMinutes($diff)
                            : $startDate->copy()->addMinutes($diff))->subMinute();

                } else {
                    $end = $startDate->copy()->startOfDay()
                        ->addHours($currentDay->to[0])
                        ->addMinutes($currentDay->to[1]);
                }
            } else {
                $end = $startDate->copy()->addHours($duration)->subMinutes(1);
            }

            $checkStartDate = $startDate->copy();
            $checkEndDate = $end->copy();

            if ($type === TimeCalculation::TIME_TYPE_SHIFT && $shiftDuration !== 24) {
                $checkStartDate->startOfDay();
                $checkEndDate->endOfDay();
            }
            /** @var Builder $exists */
            $exists = $this->freeDays()->forPeriod($checkStartDate, $checkEndDate, false);
            if ($ignore) {
                $exists->where(function (Builder $q) use (
                    $ignore
                ) {
                    $q->whereNotIn('order_component_id', $ignore)
                        ->orWhere('type', '!=', 'order');

                })->whereDoesntHave('technicalWork', function (Builder $q) use (
                    $ignore
                ) {
                    $q->whereIn('order_component_id', $ignore);
                });
            }
            if ($exists->exists()) {
                ++$i;
                continue;
            } else {

                $dates[] = (string) $startDate->copy();
            };


            ++$i;
        }
        return $dates;
    }

    function getDurationForDates(
        Carbon $dateFrom,
        Carbon $dateTo,
        $ignore = []
    ) {
        $duration = 0;
        while ($dateTo->gte($dateFrom)) {

            $exists = $this->freeDays()->forPeriod($dateFrom->copy(), $dateFrom->copy());
            $dateFrom->addDay();
            if ($ignore) {
                $exists->where(function (Builder $q) use (
                    $ignore
                ) {
                    $q->whereNotIn('order_component_id', $ignore)
                        ->orWhere('type', '!=', 'order');
                });
            }

            if ($exists->exists()) {
                continue;
            } else {
                ++$duration;
            };


        }

        return $duration;
    }


    /**
     * @param $q
     * @param  null  $domain
     * @return mixed
     */
    function scopeForDomain(
        $q,
        $domain = null
    ) {
        $domain =
            $domain
                ?: request()->header('domain');

        // logger($domain);
        if (!$domain) {
            return $q;
        }
        return $q->whereHas('region', function ($q) use (
            $domain
        ) {
            $q->whereHas('country', function ($q) use (
                $domain
            ) {
                $q->whereHas('domain', function ($q) use (
                    $domain
                ) {
                    is_array($domain)
                        ? $q->whereIn('alias', $domain)
                        : $q->whereAlias($domain);
                });
            });
        });
    }

    /**
     * @param $q
     * @param  null  $id
     * @return mixed
     */
    function scopeForRegionalRepresentative(
        $q,
        $id = null
    ) {
        $id =
            $id
                ?: Auth::id();
        if (Auth::user()->isSuperAdmin()) {
            return $q;
        }
        return $q->whereHas('user', function ($q) use (
            $id
        ) {
            $q->forRegionalRepresentative($id);
        });
    }


    /**
     * @return mixed
     */
    function getCurrencyInfoAttribute()
    {
        return Currency::getByCode($this->company_branch->currency);
    }

    /**
     * @return string
     */
    public function getFullAddressAttribute()
    {

        $region = $this->region->name;
        $city =
            $this->city
                ? $this->city->name
                : false;
        $address = $this->attributes['address'];

        return $region.($city
                ? ', '.$city.', '
                : ', ').$address;
    }

    function getHasCalendarAttribute()
    {
        return $this->freeDays()->exists();
    }

    /**
     * @param $value
     * @return string
     */
    public function getAddressAttribute($value)
    {
        $region = $this->region->name;
        $city =
            $this->city
                ? $this->city->name
                : '';
        if (!$value) {
            return $region.' '.$city;
        }

        return $value;
    }

    //function getLastTechnicalWorkAttribute()
    //{
    //    $day = $this->freeDays()->whereHas('technicalWork')->orderBy('id', 'desc')->first();
//
    //    return $day
    //        ? $day->technicalWork
    //        : null;
    //}

    function lastTechnicalWork()
    {
        return $this->hasOne(TechnicalWork::class)->latest('id');
    }

    function getCurrentTechnicalWorkAttribute()
    {
        $day = $this->freeDays()->whereHas('technicalWork')
            ->forPeriod(now(), now()->endOfDay(), false)->orderBy('id', 'desc')->first();

        return $day
            ? $day->technicalWork
            : null;
    }

    function getCurrentAddressAttribute()
    {


        return $this->current_order
            ? $this->current_order->order->address
            : $this->address;
    }

    function getCurrentOrderAttribute()
    {
        /** @var OrderComponent $position */
        $calendar = $this->freeDays()
            ->whereHas('orderWorker', fn($q) => $q->where('status', Order::STATUS_ACCEPT)->forPeriod(now(), now()))->first();

        if (!$calendar) {
            return null;
        }
        $position = $calendar->orderWorker;

        $order = $position->order()->setEagerLoads([])->first();
        if($position->actual) {
            $position->is_overdue = $position->actual->date_to->gt($position->date_to);
        }
       // $position = $position->actual ? $position->actual  : $position;
        $position->order = $order;
        $position->address = $order->address;
        $position->customer = $order->customer;
        $position->manager = $order->manager;
        return $position;
    }

    function onBase()
    {
        return $this->address === $this->address;
    }

    function onService()
    {
        return !!$this->current_technical_work;
    }

    function plans()
    {
        return $this->hasMany(TechnicalWorkPlan::class, 'machinery_id');
    }


    /**
     * @return mixed|string
     */
    function getEditAddressAttribute()
    {
        $region = $this->region->name;
        $city =
            $this->city
                ? $this->city->name
                : '';
        $address = $region.' '.$city;
        return ($this->address == $address)
            ? ''
            : $this->address;

    }

    /**
     * @param $value
     * @return string
     */
    public function getNameAttribute($value)
    {
        if ($value) {
            return $value;
        }
        $this->_type->localization();

        $type = $this->_type->name;
        $number =
            $this->number
                ?: $this->serial_number;
        $brand = $this->brand->name ?? '';
        $model =
            $this->model
                ? " {$this->model->name} "
                : ' ';
        $boardNumber =
            $this->board_number
                ? " / {$this->board_number}"
                : '';
        $newName = "{$type} {$brand}{$model}{$number}{$boardNumber}";
        if (!$value && $this->id) {

            Machinery::query()->where('id', $this->id)
                ->update([
                    'name' => $newName
                ]);
            /* $this->name = $newName;
             $this->save();*/
            return $newName;
        }

        return $newName;
    }

    /**
     * @return mixed|string
     */
    function getEditNameAttribute()
    {
        $type = $this->_type->name;
        $number = $this->number;
        $brand = $this->brand->name ?? '';
        $full = $type.' '.$number.' '.$brand;
        return ($this->name == $full)
            ? ''
            : $this->name;

    }

    /**
     * @param $query
     * @param $region
     * @param $types
     * @return mixed
     */
    function scopeCheckProposal(
        $query,
        $region,
        $types
    ) {
        return $query->whereIn('type', $types)
            ->where('region_id', $region);
    }

    /**
     * @param  Carbon  $date_start
     * @param  Carbon  $date_end
     * @return bool
     */
    function isAvailable(
        Carbon $date_start,
        Carbon $date_end
    ) {
        $coll = $this->freeDays()->where(function ($q) {
            $q->where('type', 'busy')
                ->orWhere('type', 'order');
        })
            ->where(function ($q) use (
                $date_start,
                $date_end
            ) {
                $q->where(function ($q) use (
                    $date_start,
                    $date_end
                ) {
                    $q->whereDate('startDate', '>=', $date_start->format('Y-m-d'));
                    $q->whereDate('startDate', '<=', $date_end->format('Y-m-d'));
                })->orWhere(function ($q) use (
                    $date_start,
                    $date_end
                ) {
                    $q->whereDate('endDate', '>=', $date_start->format('Y-m-d'));
                    $q->whereDate('endDate', '<=', $date_end->format('Y-m-d'));
                });
            })
            ->get();
        return $coll->isEmpty();
    }

    /**
     * @param $val
     */
    function setNumberAttribute($val)
    {
        $this->attributes['number'] = mb_strtolower(trim(str_replace(' ', '', $val)));
    }

    /**
     * @param $q
     * @param  Carbon  $startDate
     * @param  Carbon  $endDate
     * @param  null  $order_type
     * @param  null  $duration
     * @return mixed
     */
    function scopeCheckAvailable(
        $q,
        Carbon $startDate,
        Carbon $endDate,
        $order_type = null,
        $duration = null
    ) {

        if ($order_type) {

            if ($order_type === 'hour') {
                $q->where(function ($q) use (
                    $duration
                ) {
                    $q->where('tariff_type', Tariff::TIME_CALCULATION)
                        ->where('min_order_type', '!=', 'shift')
                        ->where('min_order', '<=', $duration
                            ?: 1)
                        ->orWhere(function ($q) use (
                            $duration
                        ) {
                            $q->whereIn('tariff_type', [Tariff::DISTANCE_CALCULATION, Tariff::CONCRETE_MIXER])
                                ->where('min_order', '<=', $duration
                                    ?: 1);
                        });
                });

            }

            if ($order_type === 'shift') {
                $q->where(function ($q) use (
                    $order_type,
                    $duration
                ) {
                    $q->where('tariff_type', Tariff::TIME_CALCULATION)
                        ->where(function ($q) use (
                            $order_type,
                            $duration
                        ) {
                            $q->where('min_order', '<=', (int) $duration)
                                ->where('min_order_type', $order_type);
                        })->orWhere('min_order_type', '!=', 'shift');
                });

            }
        }

        return $q->whereDoesntHave('freeDays', function ($q) use (
            $endDate,
            $startDate
        ) {

            $q->forPeriod($startDate->copy(), $endDate->copy(), false);
            /* $q->whereBetween('startDate', [$startDate, $endDate])
                 ->orWhereBetween('endDate', [$startDate, $endDate]);*/
            /*     $q->where(function ($q) use ($startDate) {
                     $q->where('startDate', '<=', $startDate);
                     $q->where('endDate', '>=', $startDate);
                 })->orWhere(function ($q) use ($endDate, $startDate) {
                     $q->where('startDate', '<=', $endDate);
                     $q->where('endDate', '>=', $endDate);
                 });*/
        });
    }

    function scopeRented(
        $q,
        $condition = true
    ) {
        return $q->where('is_rented', $condition);
    }

    function scopeCompanyRented(
        $q,
        $condition = true
    ) {
        return $q->where('is_rented_in_market', $condition);
    }

    function scopeSold(
        $q,
        $condition
    ) {
        return $q->where('read_only', $condition);
    }

    /**
     * Привзяка к позициям заявки созданной из маркетплейса
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    function leadPositions()
    {
        return $this->belongsToMany(LeadPosition::class, 'dispatcher_lead_positions_machineries');
    }

    function base()
    {
        return $this->belongsTo(MachineryBase::class, 'base_id');
    }

    function defaultBase()
    {
        return $this->belongsTo(MachineryBase::class, 'default_base_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function offers()
    {
        return $this->belongsToMany(Offer::class, 'machine_offer');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    function advert()
    {
        return $this->belongsTo(Advert::class, 'advert_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    function auction()
    {
        return $this->hasOne(Auction::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    function sale()
    {
        return $this->hasOne(Sale::class);
    }

    /**
     * @return string
     */
    function getSumHourFormatAttribute()
    {
        return number_format($this->sum_hour / 100, 0, ',', ' ');
    }

    /**
     * @return mixed
     */
    function getSumHourAttribute()
    {

        /*
                $costPerHourUnit = TariffUnitCompare::forBranch($this->company_branch_id)
                    ->where('amount', 1)
                    ->whereType(\Modules\ContractorOffice\Services\Tariffs\TimeCalculation::TIME_TYPE_HOUR)
                    ->first();

                $price = $this->prices
                    ->where('min', 1)
                    ->where('unit_compare_id', $costPerHourUnit->id)
                    ->where('type', TariffGrid::WITHOUT_DRIVER)
                    ->first();

                $price = $price->gridPrices->where('price_type', Price::TYPE_CASHLESS_WITHOUT_VAT)->first();*/

        $data = $this->calculateCost(\Modules\ContractorOffice\Services\Tariffs\TimeCalculation::TIME_TYPE_HOUR, 1, request()->pay_type ?: Price::TYPE_CASHLESS_WITHOUT_VAT);
        return $data['price'];
    }

    function getTariffGridInfo()
    {
        $arr = [];

        foreach (TariffUnitCompare::forBranch($this->company_branch_id)->get() as $unit) {
            $price = $this->prices
                ->where('min', 1)
                ->where('unit_compare_id', $unit->id)
                ->where('type', TariffGrid::WITHOUT_DRIVER)
                ->first();
            if ($price) {
                $price = $price->gridPrices->where('price_type',request()->pay_type ?: Price::TYPE_CASHLESS_WITHOUT_VAT)->first();
                $arr[] = [
                    'name' => $unit->name,
                    'cost' => $price->price ?? 0
                ];
            }
        }

        return $arr;
    }

    function getSumByPriceType(
        $timeType,
        $priceType
    ) {
        /*        $costPerHourUnit = TariffUnitCompare::forBranch($this->company_branch_id)
                    ->where('amount', 1)
                    ->whereType($timeType)
                    ->first();

                $price = $this->prices
                    ->where('min', 1)
                    ->where('unit_compare_id', $costPerHourUnit->id)
                    ->where('type', TariffGrid::WITHOUT_DRIVER)
                    ->first();

                $price = $price->gridPrices->where('price_type', $priceType)->first();*/
        return $this->calculateCost($timeType, 1, $priceType) ?? 0;
    }

    function scopeSearchByCost(
        Builder $q,
        $timeType,
        $costFirst,
        $costSecond
    ) {

        $q->whereHas('prices', function (Builder $q) use (
            $costFirst,
            $costSecond,
            $timeType
        ) {

            $q->whereHas('gridPrices', function (Builder $q) use (
                $costFirst,
                $costSecond,
                $timeType
            ) {

                $q->whereHas('tariffGrid', function ($q) use (
                    $timeType
                ) {

                    $q->whereHas('unitCompare', function (Builder $q) use (
                        $timeType
                    ) {
                        $q->where('amount', 1)
                            ->whereType($timeType);
                    });
                });

                $q->where('price_type', Price::TYPE_CASHLESS_WITHOUT_VAT);
                $q->whereBetween('price', [$costFirst, $costSecond]);

            });
        });

        return $q;
    }

    /**
     * @return mixed
     */
    function getSumDayAttribute()
    {
        $costPerShiftUnit = TariffUnitCompare::forBranch($this->company_branch_id)
            ->where('amount', 1)
            ->whereType(\Modules\ContractorOffice\Services\Tariffs\TimeCalculation::TIME_TYPE_SHIFT)
            ->first();

        try {
            $price = $this->prices
                ->where('min', 1)
                ->where('unit_compare_id', $costPerShiftUnit->id)
                ->where('type', TariffGrid::WITHOUT_DRIVER)
                ->first();

            $price = $price->gridPrices->where('price_type', request()->pay_type ?: Price::TYPE_CASHLESS_WITHOUT_VAT)->first();
            return $price->price;
        } catch (\Exception $exception) {

        }

        return 0;

    }

    /**
     * @return string
     */
    function getBrandNameAttribute()
    {
        return $this->brand->name ?? '';
    }

    /**
     * @return string
     */
    function getTypeNameAttribute()
    {
        return $this->_type->name ?? '';
    }

    /**
     * @return string
     */
    function getSumDayFormatAttribute()
    {
        return number_format($this->sum_day / 100, 0, ',', ' ');
    }


    /**
     * @param $files
     * @param  bool  $update
     * @return $this
     */
    function makeScans(
        $files,
        bool $update = false
    ) {
        $scans =
            ($update)
                ? json_decode($this->scans, true)
                : [];
        foreach ($files as $file) {
            $extension = $file->getClientOriginalExtension();
            $fileName = str_random(5)."-".date('his')."-".str_random(3).".".$extension;
            $folderpath = public_path('images');
            $file->move($folderpath, $fileName);
            Image::make($folderpath.'/'.$fileName)->save($folderpath.'/'.$fileName, 50);
            $scans[] = 'images/'.$fileName;
        }
        $this->scans = json_encode($scans);
        return $this;
    }


    /**
     * @return |null
     */
    function getLatAttribute()
    {
        return explode(',', $this->coordinates)[0] ?? null;
    }

    /**
     * @return |null
     */
    function getLngAttribute()
    {
        return explode(',', $this->coordinates)[1] ?? null;
    }

    /**
     * @param  Carbon  $minDate
     * @param  Carbon  $maxDate
     * @param $proposal_id
     * @return $this
     */
    function setOrderDates(
        Carbon $minDate,
        Carbon $maxDate,
        $proposal_id
    ) {
        $start_order = $minDate;
        $end_order = $maxDate;

        /*  $this->freeDays()
              ->where('type', 'busy')
              ->whereDate('startDate', '=', $minDate)
              ->whereDate('endDate', '=', $maxDate)->delete();

          $period = $this->freeDays()
              ->where('type', 'busy')
              ->whereDate('startDate', '<=', $minDate)
              ->whereDate('endDate', '>=', $maxDate)
              ->first();
          if ($period) {
              $end = (clone $period->endDate);
              $m = (clone $minDate)->subDay(1);
              $period->endDate = $m;
              $period->save();
              $max = (clone  $maxDate);
              if ($end->gt($max)) {
                  FreeDay::create([
                      'startDate' => $maxDate->startOfDay()->addDay(1),
                      'endDate' => $end->startOfDay(),
                      'machine_id' => $this->id,
                      'type' => 'busy',
                  ]);
              }
          }

          $this->freeDays()
              ->where('type', 'free')
              ->whereDate('startDate', '>=', $minDate)
              ->whereDate('endDate', '<', $maxDate)->delete();*/


        FreeDay::create([
            'machine_id' => $this->id,
            'startDate' => $start_order,
            'endDate' => $end_order,
            'type' => 'order',
            'proposal_id' => $proposal_id,
        ]);

        return $this;
    }

    /**
     * @param  Carbon  $minDate
     * @param  Carbon  $maxDate
     * @return $this
     */
    function setFree(
        Carbon $minDate,
        Carbon $maxDate
    ) {

        $this->freeDays()
            ->where('type', 'busy')
            ->where('startDate', '>=', $minDate)
            ->where('endDate', '<=', $maxDate)
            ->delete();
        $this->freeDays()->whereType('busy')
            ->whereBetween('startDate', [$minDate, $maxDate])
            ->update(['startDate' => $maxDate]);
        $this->freeDays()->whereType('busy')
            ->whereBetween('endDate', [$minDate, $maxDate])
            ->update(['endDate' => $minDate]);

        return $this;
    }

    /**
     * @param  Carbon  $start_date
     * @param  Carbon  $end_date
     * @return array
     */
    private function generateDateRange(
        Carbon $start_date,
        Carbon $end_date
    ) {
        $dates = [];

        for ($date = $start_date; $date->lte($end_date); $date->addDay()) {
            $dates[] = $date->format('Y/m/d H:i:s');
        }

        return $dates;
    }

    /**
     * @return array
     */
    function formReserve()
    {

        $dates = [];
        foreach ($this->offers->where('is_win', 0)->all() as $key => $offer) {

            $m = (clone $offer->proposal->date);

            $dates[] = [
                'start' => $offer->proposal->date->startOfDay(),
                'end' => $m->addDays($offer->proposal->days - 1)->startOfDay(),
                'color' => 'red',
                'title' => 'Заявка',
                'textColor' => 'white',
                'overlap' => true,
            ];

        }
        return $dates;
    }


    /**
     * @return string
     */
    function getCategoryImageAttribute()
    {
        return $this->_type->photo
            ?: 'img/no_product.png';
    }

    /**
     * @return mixed
     */
    function getCategoryIdAttribute()
    {
        return $this->type;
    }

    /**
     * @param $val
     * @return false|string
     */
    function getPhotoAttribute($val)
    {
        return json_decode($val)
            ? $val
            : json_encode([]);
    }

    /**
     * @return array
     */
    function getPhotosAttribute()
    {
        return json_decode($this->photo, true)
            ?: [];
    }


    /**
     * @return bool
     */
    function hasTelematic()
    {
        return $this->telematics || $this->company_branch->integrations()->exists();
    }

    /**
     * @return bool
     */
    function hasOwnPhotos()
    {
        return json_decode($this->photo)
            ? true
            : false;
    }

    /**
     * @param  bool  $save
     * @return $this
     */
    function generateChpu($save = false)
    {
        $brand = Brand::find($this->brand_id)->name ?? '';
        $this->alias = generateChpu("{$brand}-{$this->id}");
        if ($save) {
            $this->save();
        }

        return $this;
    }

    /**
     *
     */
    function generateSeoPhoto()
    {
        $machine = $this->refresh();


        $this->moveScans();

        if (!$this->hasOwnPhotos()) {
            return;
        }

        $brand = ($machine->brand->alias ?? false);
        $brand =
            $brand
                ? $brand.'_'
                : '';
        $vehicle_folder = "images/vehicles/{$this->id}";

        /*  if (!Storage::disk('images')->exists("vehicles/{$this->id}")) {
              Storage::disk('public_disk')->makeDirectory($vehicle_folder);
          }*/

        $new_name =
            "{$vehicle_folder}/arenda_{$machine->_type->alias}_{$brand}{$machine->city->alias}_{$machine->id}_{$machine->user->id}";
        $updating = false;
        $arr = [];
        $tmp_path = config('app.upload_tmp_dir');
        foreach ($machine->photos as $key => $photo_m) {

            if (!Str::contains($photo_m, [
                $tmp_path,
                $vehicle_folder
            ])) {
                continue;
            }

            $piece = explode('.', $photo_m);
            $ext = array_pop($piece);

            $current = "{$new_name}_{$key}.{$ext}";
            $arr[$key] = $current;

            if ($photo_m !== $current) {
                $current_exists = Storage::disk()->exists($current);
                $exists = Storage::disk()->exists($photo_m);

                if ($exists) {
                    if ($current_exists) {
                        Storage::disk()->delete($current);
                    }
                    $updating = true;
                    Storage::disk()->move($photo_m, $current);

                }
            }
        }
        if ($updating) {
            $machine->update([
                'photo' => json_encode($arr)
            ]);
        }

        $files = Storage::disk()->files($vehicle_folder);

        foreach ($files as $originalName) {

            $file = $originalName;

            if (!in_array($file, $arr)) {
                Storage::disk()->delete($originalName);
            }
        }
    }

    /**
     *
     */
    function moveScans()
    {
        $tmp_path = config('app.upload_tmp_dir');
        $vehicle_folder = "images/vehicles/{$this->id}/scans";
        $scans = json_decode($this->scans, true);
        if (!is_array($scans)) {
            return;
        }
        $update = false;

        foreach ($scans as $key => $scan) {

            $str = str_replace("{$tmp_path}/", '', $scan);
            $exist = Storage::disk()->exists($str);

            if (!Str::contains($scan, [$tmp_path])) {
                continue;
            }

            $ext = getFileExtensionFromString($scan);
            $current = "scan_{$key}.{$ext}";
            $new_name = "{$vehicle_folder}/{$current}";

            if ($exist && $scan !== $new_name) {
                Storage::disk()->move($scan, $new_name);
                $scans[$key] = $new_name;
                $update = true;
            }
        }

        if ($update) {
            $this->update(['scans' => json_encode($scans)]);
        }

        $files = Storage::disk()->files($vehicle_folder);

        foreach ($files as $originalName) {

            $file = $originalName;

            if (!in_array($file, $scans)) {
                Storage::disk()->delete($originalName);
            }
        }
    }

    /**
     * @param $photos
     */
    function putPhotosFromBase64($photos)
    {
        $array = [];
        foreach ($photos as $photo) {
            $rand = uniqid();
            $path = "images/vehicles/{$this->id}/{$rand}.jpeg";
            Storage::disk()->put($path, base64_decode($photo));
            $array[] = $path;
        }
        $this->update(['photo' => json_encode($array)]);
    }

    /**
     * @param  Machinery  $machine
     * @return array
     */
    static function integrationMap(Machinery $machine)
    {
        return [
            'id' => $machine->id,
            'licence_plate' => $machine->number,
            'rent_price' => $machine->sum_hour / 100,
            'shift_rent_price' => $machine->sum_day / 100,
            'shift_duration' => $machine->change_hour * 1,
            'name' => $machine->name,
            'machine_type' => $machine->machine_type,
            'address' => $machine->address,
            'region_id' => $machine->region_id,
            'city_id' => $machine->city_id,
            'category_id' => $machine->type,
            'brand_id' => $machine->brand_id,
            'coordinates' => $machine->coordinates,
            'user_id' => $machine->creator_id,
        ];
    }

    /**
     * @param  Machinery  $machine
     * @return array
     */
    static function contractorMap(Machinery $machine)
    {
        return [
            'id' => $machine->id,
            'licence_plate' => $machine->number,
            'machine_type' => $machine->machine_type,
            'rent_price' => $machine->sum_hour / 100,
            'shift_rent_price' => $machine->sum_day / 100,
            'shift_duration' => $machine->change_hour * 1,
            'name' => $machine->name,
            'address' => $machine->address,
            'region_id' => $machine->region_id,
            'is_rented' => $machine->is_rented,
            'city_id' => $machine->city_id,
            'category_id' => $machine->type,
            'brand_id' => $machine->brand_id,
            'coordinates' => $machine->coordinates,
            'free_days' => $machine->freeDays,
            'optional_attributes' => $machine->optional_attributes,
            'delivery_radius' => $machine->delivery_radius,
            'currency' => $machine->currency,
            'currency_info' => $machine->currency_info,
            'scans' => json_decode($machine->scans, true),
            'photo' => json_decode($machine->photo, true),
        ];
    }

    /**
     *
     */
    function deletePhotos()
    {
        if (!json_decode($this->photo, true)) {
            return;
        }
        foreach ($this->photos as $photo_m) {
            $exists = Storage::disk()->exists($photo_m);

            if ($exists) {
                Storage::disk()->delete($photo_m);
            }
        }
    }

    /**
     * @return mixed
     */
    function getRentUrlAttribute()
    {
        return (\request()->header('domain') === 'ru' || app()->getLocale() === 'ru')
            ? str_replace(env('APP_ROUTE_URL'), 'trans-baza.ru',
                route('show_rent', [$this->_type->alias, $this->region->alias, $this->city->alias, $this->alias]))
            : str_replace(env('APP_ROUTE_URL'), 'kinosk.com', route('australia_directory', [
                'country' => $this->region->country->domain->alias,
                'locale' => $this->region->country->domain->options['default_locale'],
                'category_alias' => $this->_type->alias,
                'region' => $this->region->alias,
                'city' => $this->city->alias,
                'alias' => $this->alias
            ]));
    }


    /**
     * @param $query
     * @param $lat
     * @param $lng
     * @return mixed
     */
    function scopeOrWhereInCircle(
        $query,
        $lat,
        $lng
    ) {
        $haversine = "(6371 * acos(cos(radians(".$lat."))  
                    * cos(radians(ST_x(`coordinates`))) 
                    * cos(radians(ST_y(`coordinates`)) 
                    - radians(".$lng.")) 
                    + sin(radians(".$lat.")) 
                    * sin(radians(ST_x(`coordinates`)))))";

        $query->selectRaw("*, {$haversine} AS distance")
            //->orWhereRaw("{$haversine} < ?", [$distance]);
            ->orWhereRaw("{$haversine} < delivery_radius");


        return $query;
    }

    function isInCircle($coordinates)
    {
        $earth_radius = 6371;

        [$latitude2, $longitude2] = explode(',', $this->coordinates);
        [$latitude1, $longitude1] = explode(',', $coordinates);
        $dLat = deg2rad($latitude2 - $latitude1);
        $dLon = deg2rad($longitude2 - $longitude1);

        $a =
            sin($dLat / 2) * sin($dLat / 2) + cos(deg2rad($latitude1)) * cos(deg2rad($latitude2)) * sin($dLon / 2) * sin($dLon / 2);
        $c = 2 * asin(sqrt($a));
        $d = $earth_radius * $c;

        return $this->delivery_radius >= $d;
    }

    /**
     * @param $query
     * @param $lat
     * @param $lng
     * @param  bool  $whereHasQuery
     * @return mixed
     */
    function scopeWhereInCircle(
        $query,
        $lat,
        $lng,
        $whereHasQuery = false
    ) {
        $haversine = "(6371 * acos(cos(radians(".$lat."))  
                    * cos(radians(ST_x(`coordinates`))) 
                    * cos(radians(ST_y(`coordinates`))  
                    - radians(".$lng.")) 
                    + sin(radians(".$lat.")) 
                    * sin(radians(ST_x(`coordinates`)))))";

        if ($whereHasQuery) {
            $query
                // ->select('*')
                //   ->selectRaw("delivery_radius, {$haversine} AS distance")
                ->whereRaw("{$haversine} < delivery_radius");
        } else {
            $query
                ->selectRaw("*, {$haversine} AS distance")
                ->whereRaw("{$haversine} < delivery_radius");
        }


        return $query;
    }

    /**
     * Получить стоиомсть техники по типу налогообложения.
     * @param $type
     * @return mixed
     */
    function getPriceByType($type)
    {
        return $this->prices->where('type', $type)->first();
    }


    /**
     * @param  Carbon  $date_from
     * @param  Carbon  $date_to
     * @return float|int
     */
    function calculateCostByDates(
        Carbon $date_from,
        Carbon $date_to
    ) {
        $days = $date_to->diffInDays($date_from) + 1;

        return $this->sum_day * $days;
    }


    /**
     * @param  mixed  ...$params  , $order_type, $duration, $payType, $waypoints
     * @return mixed
     */
    function calculateCost(...$params/*$order_type, $duration, $payType = Price::TYPE_CASHLESS_WITHOUT_VAT*/)
    {
        //   $params = is_array($params) ? $params : func_get_args();

        /* $price = $this->getPriceByType($payType);

         return $order_type === 'shift'
             ? $price->cost_per_shift * $duration
             : $price->cost_per_hour * $duration;*/
        $result = [];
        $result[] = $this;
        $result = array_merge($result, array_values($params));

        return $this->tariff->calculateCost($result);
    }

    /**
     * Расчет стоимости доставки
     * @param $point
     * @return float|int|null
     */
    function calculateDeliveryCost(
        $distance,
        $type,
        $pay_type = Price::TYPE_CASHLESS_WITHOUT_VAT
    ) {
        /*if ($this->is_contractual_delivery) {
            return 0;
        }*/
        // $distance = $this->calculateDeliveryDistance($point);
        if ($distance === false) {
            return null;
        }
        $price = 0;
        $calc_distance = $distance;
        $collection = $this->deliveryPrices()->where('type', $type)->get();

        foreach ($collection as $item) {

            // if(!isset($current_price)) {

            $current_price = $item->grid_prices->where('price_type', $pay_type)->first();

            if (!$current_price) {
                return 0;
            }
            // continue;
            //  }

            $calc_distance -= $item->min;


            if ($calc_distance <= 0) {
                $price += ($item->is_fixed
                    ? $current_price->price
                    : ($current_price->price * ($item->min + $calc_distance)));
                //logger(json_encode($item));
                // logger($item->min + $calc_distance);
                // logger( ($item->is_fixed ? $current_price->price : ($current_price->price * ($item->min + $calc_distance))));
                // logger($calc_distance);
                break;
            } else {
                $price += ($item->is_fixed
                    ? $current_price->price
                    : ($current_price->price * $item->min));
                //   logger($price);
            }

            $current_price = $item->grid_prices->where('price_type', $pay_type)->first();
            if (!$current_price) {
                return 0;
            }

        }
        try {
            if ($calc_distance > 0) {
                $price += ($item->is_fixed
                    ? $current_price->price
                    : ($calc_distance * $current_price->price));
            }
        } catch (\Exception $exception) {
            return 0;
        }

        if ($this->company_branch->getSettings()->price_without_vat && $pay_type === Price::TYPE_CASHLESS_VAT) {
            $price = Price::removeVat($price, $this->company_branch->domain->country->vat);
        }
        return $price;

        /*   if ($distance > $this->free_delivery_distance) {
               $distance = $distance - $this->free_delivery_distance;

               return round($distance * $this->delivery_cost_over);
           }*/

    }

    function setReadOnly()
    {
        $this->update([
            'read_only' => true,
            'is_rented_in_market' => false,
            'is_rented' => false,
        ]);

        return $this;
    }

    /**
     * Дистанция доставки от точки базирования техники
     * @param $delivery_coordinates
     * @return bool|float|int
     */
    function calculateDeliveryDistance($delivery_coordinates)
    {
        $a = GoogleMaps::load('distancematrix')
            ->setParam([
                'origins' => $this->coordinates,
                'destinations' => $delivery_coordinates,
                'mode' => 'driving',
                'language' => 'GB'
            ])
            ->getResponseByKey('rows.elements');
        return isset($a['rows'][0]['elements'][0]['distance']['value'])
            ? $a['rows'][0]['elements'][0]['distance']['value'] / 1000
            : false;
    }

    /**
     * Добавление телематики Trekerserver.ru
     * @return $this|bool
     */
    function attachTrekerServerTelematic()
    {
        if ($this->telematics && $this->telematics instanceof Trekerserver) {
            return true;
        }

        $treker = Trekerserver::create();

        $this->telematics()->associate($treker);
        $this->save();

        if (!$treker->getPosition()) {
            return false;
        };

        return $this;
    }

    /**
     * Удаление телематики
     * @return $this
     */
    function detachTelematics()
    {
        if ($this->telematics) {
            if ($this->telematics instanceof WialonVehicle) {
            } else {
                $this->telematics->delete();
            }
            $this->telematics_type = null;
            $this->telematics_id = null;
            $this->save();
        }
        return $this;
    }

    function documents()
    {
        return $this->morphMany(OrderDocument::class, 'order');
    }

    function lastService()
    {
        return $this->hasOne(ServiceCenter::class, 'machinery_id')
            ->where('is_plan', true)
            ->where('date_from', '<=', now())
            ->where('status_tmp', ServiceCenter::STATUS_ISSUED)
            ->latest('date_from');
    }

    function futureService()
    {
        return $this->hasOne(ServiceCenter::class, 'machinery_id')
            ->where('is_plan', true)
            ->where('date_from', '>', now())
            ->orderBy('date_from');
    }

    function createFutureService()
    {

    }

    function getShiftDurationAttribute()
    {
        return $this->change_hour;
    }
}
