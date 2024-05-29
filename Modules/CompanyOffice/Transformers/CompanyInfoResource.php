<?php

namespace Modules\CompanyOffice\Transformers;

use Illuminate\Http\Resources\Json\JsonResource;
use Modules\CompanyOffice\Entities\Company\CompanyBranch;

class CompanyInfoResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param \Illuminate\Http\Request
     * @return array
     */
    public function toArray($request)
    {
        $settings = $this->settings;
        /** @var CompanyBranch $branch */
        $branch = $this->branches->first();
        $vat = false;
        if($branch) {
            $vat = $branch->getSettings()->price_without_vat;
            $oneC = $branch->OneCConnection;
        }
        return [
            'id' => $this->id,
            'name' => $this->name,
            'branches' => $this->branches,
            'alias' => $this->alias,
            'domain_id' => $this->domain_id,
            'price_without_vat' => $vat,
            'oneC' => $oneC ?? null,
            'options' => $this->options,
            'requisites' => $branch?->getAllRequisites(),
            'domain' => $this->domain,
            'indexing' => $settings ? $settings->indexing : false,
            'info_contacts' => $settings ?
                [
                    'contact_address' => $settings->contact_address,
                    'contact_phone' => $settings->contact_phone,
                    'contact_email' => $settings->contact_email,
              /*      $this->mergeWhen($request->filled('seoOptions'), [
                        'catalog_seo_text' => $settings->catalog_seo_text
                    ])*/
                ]
                : null,


        ];
    }
}
