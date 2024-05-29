<?php

namespace Modules\ContractorOffice\Http\Requests;

use App\Machinery;
use App\Machines\OptionalAttribute;
use App\Machines\Type;
use App\Service\RequestBranch;
use Carbon\Carbon;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Modules\ContractorOffice\Entities\System\Tariff;
use Modules\ContractorOffice\Entities\Vehicle\Price;
use Modules\ContractorOffice\Rules\Vin;
use Modules\Integrations\Rules\Coordinates;
use Modules\RestApi\Entities\Currency;

class CreateVehicle extends FormRequest
{
    private $companyBranch;

    public function __construct(
        array $query = [],
        array $request = [],
        array $attributes = [],
        array $cookies = [],
        array $files = [],
        array $server = [],
        $content = null)
    {
        parent::__construct($query, $request, $attributes, $cookies, $files, $server, $content);

        $this->companyBranch = app()->make(RequestBranch::class)->companyBranch;
    }

    function prepareForValidation()
    {
        $this->companyBranch = app()->make(RequestBranch::class)->companyBranch;
        $this->merge([
            'licence_plate' => trimLicencePlate($this->licence_plate),
            'type_eq'       => $this->category_id,
        ]);
    }


    function messages()
    {
        return Machinery::getFieldsMessages();
    }


    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {

        $arr = [
            'name'            => 'nullable|string|min:3|max:255',
            'machine_type'    => 'required|in:machine,equipment',
            'model_id'        => 'required|exists:machinery_models,id',
            'region_id'       => 'required|integer|exists:regions,id',
            'base_id'         => 'nullable|exists:machinery_bases,id',
            'default_base_id' => 'nullable|exists:machinery_bases,id',
            'telematics_type' => 'nullable|in:none,' . implode(',', array_keys(Machinery::getTelematics())),
            'shift_duration'  => 'required|integer|min:1|max:24',
            'delivery_radius' => 'required|integer|min:1|max:5000',

            'board_number' => [
                'nullable',
                'string',
                'max:255',
                Rule::unique('machineries')
                    ->where('company_branch_id', $this->companyBranch->id)
                    ->where(function ($q) {
                        $q->where('board_number', $this->board_number);
                        if ($this->route('id')) {
                            $q->where('id', '!=', $this->route('id'));
                        }
                    })
            ],

            'description'             => 'nullable|string|max:1000',
            'insurance_premium_cost'  => 'required|integer|min:0|max:999999',
            'free_delivery_distance'  => 'required|integer|min:0|max:500',
            'delivery_cost_over'      => 'required|integer|min:0|max:1000000',
            'is_contractual_delivery' => 'required|boolean',
            'is_rented'               => 'required|boolean',
            'pledge_cost'             => 'nullable|numeric|min:0',
            'selling_price'           => 'nullable|numeric|min:0',
            'available_for_sale'      => 'nullable|boolean',

            'market_price'          => 'nullable|numeric|min:0',
            'market_price_currency' => [
                ($this->input('market_price')
                    ? 'required'
                    : 'nullable'),
                 Rule::in(
                     $this->companyBranch->domain->currencies->pluck('code')->toArray()
                 )
            ],
            'currency' => [
                ($this->input('currency')
                    ? 'required'
                    : 'nullable'),
                Rule::in(
                    $this->companyBranch->domain->currencies->pluck('code')->toArray()
                )
            ],

            'tariff_type' => 'required|in:' . (implode(',',Tariff::query()->pluck('type')->toArray() )),

            'wialon_telematic' => 'nullable|array',

            'contractual_delivery_cost' => ((toBool($this->input('is_contractual_delivery'))
                    ? 'required|'
                    : 'nullable|') . 'integer|min:0|max:1000000'),
            'serial_number'             => 'nullable|string|max:255',
            'vin'                       => [
                //Для телематики трекрсервера необходим VIN номер.
                ($this->input('telematics_type') !== 'trekerserver'
                    ? 'nullable'
                    : 'required'),
                new Vin()
            ],
            'city_id'                   => [
                'required',
                'integer',
                Rule::exists('cities', 'id')->where('region_id', $this->region_id)
            ],
            /* 'rent_price' => 'required|numeric|min:1|max:10000000',
             'shift_rent_price' => 'required|numeric|min:1|max:100000000',*/
            'brand_id'                  => 'required|exists:brands,id',
            'photo'                     => 'nullable|array|min:0|max:5',
            'photo.*'                   => 'string',
            'optional_attributes'       => [
                'nullable',
                'array',
                function (
                    $attribute,
                    $value,
                    $fail) {

                    $ids = array_keys($value);

                    $stockCntWithinArrIDs =
                        OptionalAttribute::whereTypeId($this->category_id)->whereIn('id', $ids)->count();
                    if ($stockCntWithinArrIDs != count($ids))
                        return $fail($attribute . ' не найдено.');
                }
            ],
            'optional_attributes.*'     => [
                function (
                    $attribute,
                    $value,
                    $fail) {

                    $id = last(explode('.', $attribute));

                    $attribute = OptionalAttribute::whereId($id)->firstOrFail();
                    if ($attribute->require && mb_strlen($value) === 0) {
                        return $fail('Обязателен для заполнения');
                    }
                    if (mb_strlen($value) > 255) {
                        return $fail('Длинный текст');
                    }
                    if (mb_strlen($value) !== 0) {
                        switch ($attribute->field) {
                            case 'number':
                                if (floatval($value) > $attribute->max) {
                                    return $fail("Макс. значение {$attribute->max}");
                                }
                                if (floatval($value) < $attribute->min) {
                                    return $fail("Мин. значение {$attribute->min}");
                                }
                                break;
                        }
                    }

                },
            ],

        ];

        $arr += (in_array($this->input('tariff_type'), [Tariff::DISTANCE_CALCULATION, Tariff::CONCRETE_MIXER])
            ? [
                'waypoints_price' => [
                    'required', 'array', 'min:1', 'max:20', function (
                        $attribute,
                        $value,
                        $fail) {
                        $collect = collect($value);
                        if (!$collect->where('distance', 0)->first()) {
                            return $fail('1 поле необходимо начинаться с 0 км.');
                        }
                    }
                ],

                "waypoints_price.*.distance"             => 'required|numeric|min:0|distinct',
                "waypoints_price.*.cash"                 => 'required|numeric|min:10|max:9999999',
                'waypoints_price.*.cashless_vat'         => 'required|numeric|min:10|max:9999999',
                'waypoints_price.*.cashless_without_vat' => 'required|numeric|min:10|max:9999999',
            ]
            : ([
                    'prices' => 'required|array|min:2',

                    /*'prices.*.cost_per_shift' => 'required|numeric|min:1|max:9999999',
                    'prices.*.cost_per_hour' => 'required|numeric|min:1|max:9999999',
                    'prices.*.type' => 'required|in:' . implode(',', Price::getTypes()),*/

                    'min_order_type' => 'required|in:shift,hour',
                    'min_order'      => 'required|integer|min:1|max:' . ($this->min_order_type === 'hour'
                            ? $this->input('shift_duration', 1)
                            : 30),
                ] + $this->getPriceGridValidation('prices') + $this->getPriceGridValidation('driver_prices')));

        if ($this->companyBranch->wialonAccount) {
            $arr['wialon_telematic.id'] = [
                'nullable',
                Rule::exists('wialon_vehicles', 'id')->whereIn('wialon_connection_id', [$this->companyBranch->wialonAccount->id])
            ];// 'nullable|exists:,id',
        }

        if (request()->has('coordinates')) {
            $arr = array_merge($arr, [
                'coordinates' => new Coordinates
            ]);
        }


        if ($this->machine_type === 'machine') {
            $arr = array_merge($arr,
                [
                    'category_id' => [
                        'required',
                        Rule::exists('types', 'id')->where('type', 'machine')
                    ],

                ]);
            $category = Type::find($this->category_id);
            if ($category && $category->licence_plate) {
                $arr['licence_plate'] =
                    'required|string|min:4|max:15|unique:machineries,number' . ($this->route('id')
                        ? ",{$this->route('id')}"
                        : '');
            }
        } else {
            $arr = array_merge($arr, [
                'category_id' => [
                    'required',
                    Rule::exists('types', 'id')->where('type', 'equipment')
                ]
            ]);
        }

        return $arr;
    }

    function getPriceGridValidation($key)
    {
        return [
            //"{$key}.*.is_fixed" => 'nullable|boolean',
            "{$key}.*.min"                              => 'required|integer|min:1|max:99999',
            "{$key}.*.market_markup"                    => 'nullable|integer|min:0|max:100',
            "{$key}.*.unit_compare_id"                  => [
                Rule::exists('tariff_unit_compares', 'id')->where('company_branch_id', $this->companyBranch->id)
            ],
            "{$key}.*.grid_prices"                      => 'required|array',
            "{$key}.*.grid_prices.cash"                 => 'required|numeric|min:1|max:999999',
            "{$key}.*.grid_prices.cashless_vat"         => 'nullable|numeric|min:0|max:99999999',
            "{$key}.*.grid_prices.cashless_without_vat" => 'nullable|numeric|min:0|max:99999999',
        ];
    }

    static function getDeliveryGridValidation($key)
    {
        return [
            //"{$key}.*.is_fixed" => 'nullable|boolean',
            "{$key}.*.min"                              => 'required|distinct|integer|min:1|max:99999',
            "{$key}.*.is_fixed"                         => 'nullable|boolean',
            "{$key}.*.grid_prices"                      => 'required|array',
            "{$key}.*.grid_prices.cash"                 => 'required|numeric|min:0|max:999999',
            "{$key}.*.grid_prices.cashless_vat"         => 'required|numeric|min:0|max:99999999',
            "{$key}.*.grid_prices.cashless_without_vat" => 'required|numeric|min:0|max:99999999',
        ];
    }

    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }
}
