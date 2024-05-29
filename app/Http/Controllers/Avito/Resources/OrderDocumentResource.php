<?php

namespace App\Http\Controllers\Avito\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

/**
 * @OA\Schema(
 *  @OA\Xml(name="OrderResource")
 * )
 **/
class OrderDocumentResource extends JsonResource
{
    /*
     * @param  \Illuminate\Http\Request
     * @return array
     */
    public function toArray($request)
    {

        return [
            "doc_id" => $this->id,
            "doc_type" => $this->type === 'upd' ? 2 : 1,
            "doc_url" => Storage::disk()->url($this->url),
            "doc_date" => $this->created_at
        ];
    }
}
