<?php

namespace Modules\RestApi\Transformers;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Resources\Json\ResourceCollection;

class VehicleSearch extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param \Illuminate\Http\Request
     * @return array
     */
    public function toArray($request)
    {
        if ($request->filled('withEvents')) {
            $events = $this->freeDays()
                /// ->busy()
                ->forPeriod(now(), now()->addDays(30))
                ->get();
            $events = $events->map(function ($event) {
                return [
                    'id'        => $event->id,
                    'startDate' => (string)$event->startDate,
                    'endDate'   => (string)$event->endDate,
                    'start'     => $event->start,
                    'end'       => $event->end,
                    'order'       => $event->order,
                    'title'     => trans('contractors/edit.busy'),
                    'allDay'    => $event->all_day,
                    'color'     => $event->all_day
                        ? 'red'
                        : '#6d99bc',
                ];
            });
        }
        $this->_type->localization();

        return [
            'id'                        => $this->id,
            'in_radius'                 => $this->in_radius,
            'has_act'                   => $this->has_act,
            'no_act'                   =>   [
                'order_id' => $this->no_act_order_id,
                'internal_number' => $this->no_act_internal_number,
            ],
            'address'                   => $this->address,
            'description'               => $this->description,
            'sum_hour'                  => $this->sum_hour,
            'base'                      => $this->base,
            'default_base'              => $this->defaultBase,
            'drivers'                   => $this->drivers,
            'sum_day'                   => $this->sum_day,
            'name'                      => $this->name,
            'tariff_type'               => $this->tariff_type,
            'alias'                     => $this->alias,
            'events'                    => $events ?? [],
            'coordinates'               => $this->coordinates,
            'delivery_radius'           => $this->delivery_radius,
            'currency'                  => $this->currency,
            'serial_number'             => $this->serial_number,
            'board_number'              => $this->board_number,
            'sort_order'              => $this->sort_order,
            'has_telematic'             => $this->hasTelematic(),
            'waypoints_price'           => $this->waypoints_price->distances,
            'machine_type'           => $this->machine_type,
            $this->mergeWhen($this->hasTelematic(), [
                'last_position'         => $this->telematics->last_position ?? null,
                'position_updated_date' => $this->telematics
                    ? $this->telematics->updated_at->format('d.m.Y')
                    : null,
                'position_updated_time' => $this->telematics
                    ? $this->telematics->updated_at->format('H:i')
                    : null
            ]),
            $this->mergeWhen($this->order_dates, [
                'order_dates' => $this->order_dates
            ]),
            $this->mergeWhen($this->subOwner, [
                'contractor_id' => $this->sub_owner_id,
                'contractor'    => $this->subOwner
                    ? $this->subOwner->only(['id', 'company_name'])
                    : null,
            ]),
            'free_delivery_distance'    => $this->free_delivery_distance,
            'delivery_cost_over'        => $this->delivery_cost_over,
            'is_contractual_delivery'   => $this->is_contractual_delivery,
            'contractual_delivery_cost' => $this->contractual_delivery_cost,
            'full_address'              => $this->full_address,
            'sum_day_format'            => $this->sum_day_format,
            'sum_hour_format'           => $this->sum_hour_format,
            'photo'                     => json_decode($this->photo),
            'currency_info'             => $this->currency_info,
            'rent_url'                  => $this->rent_url,
            'category'                  => $this->_type,
            'model'                     => $this->model,
            'selling_price'             => $this->selling_price,
            'pledge_cost'               => $this->pledge_cost,
            'model_id'                  => $this->model_id,
            'region'                    => $this->region,
            'licence_plate'             => $this->number,
            'city'                      => $this->city,
            'work_hours'                => $this->work_hours,
            'optional_attributes'       => $this->optional_attributes,

            'brand'    => $this->brand,
            'brand_id' => $this->brand_id,

            'shift_duration'            => (int) $this->change_hour,
            'min_order_type'            => $this->min_order_type,
            'min_order'                 => $this->min_order,
            'order'                 => $this->order,
           // 'calendar'                  => $this->freeDays,
            'tariff_grid_info'          => $this->getTariffGridInfo(),
            'creator_id'                => $this->creator_id,
            'company_branch_id'         => $this->company_branch_id,
            'show_market_price'         => $this->show_market_price,
            'show_company_market_price' => $this->show_company_market_price,
            $this->mergeWhen($this->order_cost, [
                'order_cost'      => $this->order_cost,
                'order_type'      => $this->order_type,
                'order_duration'  => $this->duration,
                'order_waypoints' => $this->order_waypoints,
                'order_params'    => $this->order_params,
                'date_from'       => (string)$this->date_from,

            ]),
        ];
    }
}
