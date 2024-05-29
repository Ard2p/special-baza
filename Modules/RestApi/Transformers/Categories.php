<?php

namespace Modules\RestApi\Transformers;

use Illuminate\Http\Resources\Json\JsonResource;

class Categories extends JsonResource
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
            'name' => $this->name,
            'type' => $this->type,
            'name_style' => $this->name_style,
            'eng_alias' => $this->eng_alias,
            'alias' => $this->alias,
            'photo' => $this->photo,

        ];
    }
}
