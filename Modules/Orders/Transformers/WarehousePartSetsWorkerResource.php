<?php

namespace Modules\Orders\Transformers;

use App\Machinery;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Orders\Entities\Order;

class WarehousePartSetsWorkerResource extends JsonResource
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
            'company_branch_id' => $this->company_branch_id,
            'id' => $this->id,
            'machinery_base_id' => $this->machinery_base_id,
        ];
    }
}
