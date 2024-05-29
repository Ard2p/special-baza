<?php

namespace Modules\AdminOffice\Transformers;

use Illuminate\Http\Resources\Json\JsonResource;

class Content extends JsonResource
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
            'title' => $this->title,
            'alias' => $this->alias,
            'keywords' => $this->keywords,
            'description' => $this->description,
            'h1' => $this->h1,
            'image_alt' => $this->image_alt,
            'is_publish' => $this->is_publish,
            'content' => $this->content,
            'image' => $this->image,
            'type' => $this->type,
            'domain_id' => $this->domain_id,
            'tags' => $this->tags->pluck('name'),
            'locale' => $this->locale,
            'domain' => $this->domain,
            'galleries' => $this->galleries,
            'federal_districts' => $this->federal_districts->pluck('id'),
        ];
    }
}
