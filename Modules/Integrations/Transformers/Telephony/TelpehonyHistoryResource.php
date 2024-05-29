<?php

namespace Modules\Integrations\Transformers\Telephony;

use App\User;
use Illuminate\Http\Resources\Json\JsonResource as Resource;
use Illuminate\Support\Facades\Auth;

class TelpehonyHistoryResource extends Resource
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
            'phone' => $this->phone,
            'status' => $this->status,
            'link' => $this->link,
            'raw_data' => $this->raw_data,
            'updated_at' => (string) $this->updated_at,
        ];
    }
}
