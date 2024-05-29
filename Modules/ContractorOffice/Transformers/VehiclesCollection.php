<?php

namespace Modules\ContractorOffice\Transformers;

use App\Machinery;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\ContractorOffice\Entities\System\TariffGrid;
use Modules\Orders\Entities\MachineryStamp;
use Modules\Orders\Entities\OrderComponent;
use Modules\Orders\Transformers\ServiceCenterResource;

class VehiclesCollection extends JsonResource
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

        $orderComponent = OrderComponent::query()
            ->where('worker_id', $this->id)
            ->where('worker_type', Machinery::class)
            ->orderBy('date_to', 'desc')
            ->where('date_to', '<=', now())
            ->accepted()
            ->first();

        if($orderComponent) {
            $exists = MachineryStamp::query()->where('order_id', $orderComponent->order_id)
                ->where('machinery_id', $this->id)
                ->where('type', 'done')->exists();

        }

        return [
            'id'                    => $this->id,
            'internal_number'       => $this->internal_number,
            'brand'                 => $this->brand,
            'base'                  => $this->base,
            'default_base'          => $this->defaultBase,
            'licence_plate'         => $this->number,
            'board_number'          => $this->board_number,
            'model_id'              => $this->model_id,
            'has_act'              =>  $exists ?? true,
            'no_act_order_id'              =>  $orderComponent?->order_id,
            'no_act_internal_number'              =>  $orderComponent?->order_internal_number,
            'model'                 => $this->model,
            'on_service'            => $this->current_technical_work,
            'on_base'               => $this->onBase(),
            'base_id'               => $this->base_id,
            'optional_attributes'               => $this->optional_attributes->map->full_name,
            'default_base_id'       => $this->default_base_id,
            'last_technical_work'   => $this->lastTechnicalWork,
            'current_address'       => $this->current_address,
            'engine_hours_after_tw'       => $this->engine_hours_after_tw,
            'days_after_tw'       => $this->days_after_tw,
            'current_order'         => $this->current_order,
            'market_price'          => $this->market_price,
            'market_price_currency' => $this->market_price_currency,
            'rent_price'            => $this->sum_hour / 100,
            'shift_rent_price'      => $this->sum_day / 100,
            'shift_duration'        => $this->change_hour * 1,
            'name'                  => $this->name,
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
            'orders_sum'            => $this->orders_sum,
            'orders_count'            => $this->orders_count,
            'serial_number'         => $this->serial_number,
            'min_order'             => $this->min_order,
            'min_order_type'        => $this->min_order_type,
            'is_free'        => $this->is_free,
            'brand_id'              => $this->brand_id,
            'tariff_type'           => $this->tariff_type,
            'rent_with_driver'      => $this->rent_with_driver,

            'prices'        => $this->prices->where('type', TariffGrid::WITHOUT_DRIVER)->toArray(),
            'driver_prices' => $this->prices->where('type', TariffGrid::WITH_DRIVER)->toArray(),

            'delivery_forward' => $this->deliveryPrices->where('type', 'forward')->toArray(),
            'delivery_back'    => $this->deliveryPrices->where('type', 'back')->toArray(),


            'waypoints_price'           => $this->waypoints_price->distances
                ?: [],
            'coordinates'               => $this->coordinates,
            'price_includes_fas'        => $this->price_includes_fas,
            'delivery_radius'           => $this->delivery_radius,
            'currency'                  => $this->currency,
            'telematics_type'           => $this->getTelematicsType(),
            'wialon_telematic'          => $this->wialon_telematic
                ?: ['id' => ''],
            'currency_info'             => $this->currency_info,
            'free_delivery_distance'    => $this->free_delivery_distance,
            'delivery_cost_over'        => $this->delivery_cost_over / 100,
            $this->mergeWhen($this->subOwner, fn() => [
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
            'has_calendar'              => $this->has_calendar,
            'insurance_premium_cost'              => $this->insurance_premium_cost,
            'last_service'              => ServiceCenterResource::make($this->lastService),
            'future_service'             => ServiceCenterResource::make($this->futureService),
            'rent_days'      => $this->freeDays()->where('type', 'order')->where('endDate', '<=', now())->count(),
            'avito_ids'              => $this->avito_ads->pluck('avito_id'),
        ];
    }
}
