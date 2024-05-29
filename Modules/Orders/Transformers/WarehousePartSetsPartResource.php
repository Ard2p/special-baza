<?php

namespace Modules\Orders\Transformers;

use App\Machinery;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Orders\Entities\Order;

class WarehousePartSetsPartResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request
     * @return array
     */
    public function toArray($request)
    {
        return [
            'id' => $this->id,
        ];
    }
}
