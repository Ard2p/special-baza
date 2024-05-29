<?php

namespace Modules\ContractorOffice\Transformers;

use Illuminate\Http\Resources\Json\JsonResource as Resource;

class CompanyWorker extends Resource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request
     * @return array
     */
    public function toArray($request)
    {
        return parent::toArray($request);
    }
}
