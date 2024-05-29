<?php

namespace Modules\ContractorOffice\Transformers;

use Illuminate\Http\Resources\Json\JsonResource;
use Modules\ContractorOffice\Entities\System\TariffGrid;
use Modules\Orders\Entities\Service\ServiceCenter;

class Vehicle extends JsonResource
{
    /**
     * Ресурс редактирования техники
     * Transform the resource into an array.
     *
     * @param \Illuminate\Http\Request
     * @return array
     */
    public function toArray($request)
    {
        $this->_type->localization();

        $this->prices->load('gridPrices');
        $this->deliveryPrices->load('grid_prices');
        $this->model?->load('characteristics');
        return [
            'id'                    => $this->id,
            'internal_number'       => $this->internal_number,
            'brand'                 => $this->brand,
            'base'                  => $this->base,
            'default_base'          => $this->defaultBase,
            'licence_plate'         => $this->number,
            'board_number'          => $this->board_number,
            'model_id'              => $this->model_id,
            'model'                 => $this->model,
            'on_service'            => $this->current_technical_work,
            'last_service'          => $this->lastService,
            'future_service'          => $this->futureService,
            'on_base'               => $this->onBase(),
            'base_id'               => $this->base_id,
            'plans'               => $this->plans,
            'default_base_id'       => $this->default_base_id,
            'last_technical_work'   => $this->lastTechnicalWork,
            'engine_hours_after_tw'   => $this->engine_hours_after_tw,
            'days_after_tw'   => $this->days_after_tw,
            'current_address'       => $this->current_address,
            'current_order'         => $this->current_order,
            'market_price'          => $this->market_price,
            'market_price_currency' => $this->market_price_currency,
            'rent_price'            => $this->sum_hour / 100,
            'shift_rent_price'      => $this->sum_day / 100,
            'shift_duration'        => $this->change_hour * 1,
            'name'                  => $this->name,
            'orders_sum'            => $this->orders_sum,
            'orders_count'          => $this->orders_count,
            'description'           => $this->description,
            'address'               => $this->address,
            'region_id'             => $this->region_id,
            'city_id'               => $this->city_id,
            'sum_hour'              => $this->sum_hour,
            'sum_day'               => $this->sum_day,
            'selling_price'         => $this->selling_price,
            'pledge_cost'           => $this->pledge_cost,
            'available_for_sale'    => $this->available_for_sale,
            'city'                  => $this->city,
            'region'                => $this->region,
            'category'              => $this->_type,
            'category_id'           => $this->type,
            'vin'                   => $this->vin,
            'serial_number'         => $this->serial_number,
            'min_order'             => $this->min_order,
            'min_order_type'        => $this->min_order_type,
            'brand_id'              => $this->brand_id,
            'tariff_type'           => $this->tariff_type,
            'rent_with_driver'      => $this->rent_with_driver,
            'rent_days'      => $this->freeDays()->where('type', 'order')->count(),

            'prices'        => $this->prices->where('type', TariffGrid::WITHOUT_DRIVER)->toArray(),
            'driver_prices' => $this->prices->where('type', TariffGrid::WITH_DRIVER)->toArray(),

            'delivery_forward' => $this->deliveryPrices->where('type', 'forward')->toArray(),
            'delivery_back'    => $this->deliveryPrices->where('type', 'back')->toArray(),


            'waypoints_price'           => $this->waypoints_price->distances
                ?: [],
            'coordinates'               => $this->coordinates,
            'price_includes_fas'        => $this->price_includes_fas,
       //     'free_days'                 => $this->freeDays,
            'optional_attributes'       => $this->optional_attributes,
            'delivery_radius'           => $this->delivery_radius,
            'currency'                  => $this->currency,
            'telematics_type'           => $this->getTelematicsType(),
            'wialon_telematic'          => $this->wialon_telematic
                ?: ['id' => ''],
            'currency_info'             => $this->currency_info,
            'free_delivery_distance'    => $this->free_delivery_distance,
            'delivery_cost_over'        => $this->delivery_cost_over / 100,
            $this->mergeWhen($this->subOwner, [
                'contractor_id' => $this->sub_owner_id,
                'contractor'    => $this->subOwner
                    ? $this->subOwner->only(['id', 'company_name'])
                    : null,
            ]),
            'is_rented'                 => $this->is_rented,
            'is_rented_in_market'       => $this->is_rented_in_market,
            'show_market_price'         => $this->show_market_price,
            'show_company_market_price' => $this->show_company_market_price,

            'is_contractual_delivery'   => $this->is_contractual_delivery,
            'contractual_delivery_cost' => $this->contractual_delivery_cost / 100,
            'created_at'                => (string)$this->created_at,
            'scans'                     => json_decode($this->scans, true),
            'photo'                     => json_decode($this->photo, true),
            'has_calendar'              => $this->has_calendar,
            'insurance_premium_cost'              => $this->insurance_premium_cost,
            'avito_id'              => $this->avito_id,
            'avito_ids'              => $this->avito_ads->pluck('avito_id'),
            'year'              => $this->year,
        ];
    }
}
