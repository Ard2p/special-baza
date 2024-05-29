<?php

namespace Modules\CompanyOffice\Entities\Company;

use App\Overrides\Model;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Modules\CompanyOffice\Services\BelongsToCompanyBranch;

class DocumentsPack extends Model
{

    protected $table = 'company_documents_packs';

    use BelongsToCompanyBranch;

    const DOCUMENT_TEMPLATES = [
        'default_contract_url',
        'default_service_contract_url',
        'default_application_url',
        'default_disagreement_url',
        'default_return_act_url',
        'default_acceptance_act_url',
        'default_cash_order_stamp',
        'default_cash_order',
        'default_single_act_url',
        'default_return_single_act_url',
        'default_single_act_services_url',
        'default_single_application_url',
        'default_single_contract_url',
        'default_single_contract_url_with_stamp',
        'default_service_return_act',
        'default_service_services_act',
        'default_invoice_stamp_url',
        'default_invoice_url',
        'default_worker_result_url',
        'default_service_act_url',
        'default_set_application_url',
        'default_set_act_url',
        'default_return_set_act_url',
        'default_upd_url',
        'default_parts_sale_invoice',
        'default_service_center_invoice',
        'default_pledge_invoice',
        'default_pledge_invoice_with_stamp',
        'default_application_url_with_stamp',
        'default_disagreement_url_with_stamp',
        'default_return_act_url_with_stamp',
        'default_acceptance_act_url_with_stamp',
        'default_single_act_url_with_stamp',
        'default_return_single_act_url_with_stamp',
        'default_single_act_services_url_with_stamp',
        'default_single_application_url_with_stamp',
        'default_service_return_act_with_stamp',
        'default_service_services_act_with_stamp',
        'default_worker_result_url_with_stamp',
        'default_service_act_url_with_stamp',
        'default_set_application_url_with_stamp',
        'default_set_act_url_with_stamp',
        'default_return_set_act_url_with_stamp',
        'default_upd_url_with_stamp',
        'default_parts_sale_invoice_with_stamp',
        'default_service_center_invoice_with_stamp',
        'default_invoice_contract_url',
        'default_invoice_contract_url_with_stamp',
        'default_contract_url_with_stamp',
        'default_avito_invoice_url',
        'default_avito_return_act',
        'default_avito_upd',
        'default_order_claims_url',
        'default_order_claims_url_html',
        'default_service_claims_url',
        'default_service_claims_url_html',
    ];

    protected $fillable = [
        'name',
        'type_from',
        'type_to',
        'default_contract_url',
        'default_service_contract_url',
        'default_application_url',
        'default_disagreement_url',
        'default_return_act_url',
        'default_acceptance_act_url',
        'default_invoice_stamp_url',
        'default_invoice_url',
        'default_worker_result_url',
        'default_service_act_url',
        'default_single_act_url',
        'default_return_single_act_url',
        'default_single_act_services_url',
        'default_single_application_url',
        'default_service_services_act',
        'default_service_return_act',
        'default_set_application_url',
        'default_set_act_url',
        'default_return_set_act_url',
        'default_upd_url',
        'default_cash_order',
        'default_cash_order_stamp',
        'company_branch_id',
        'default_parts_sale_invoice',
        'default_service_center_invoice',
        'default_application_url_with_stamp',
        'default_disagreement_url_with_stamp',
        'default_return_act_url_with_stamp',
        'default_acceptance_act_url_with_stamp',
        'default_single_act_url_with_stamp',
        'default_return_single_act_url_with_stamp',
        'default_single_act_services_url_with_stamp',
        'default_single_application_url_with_stamp',
        'default_service_return_act_with_stamp',
        'default_service_services_act_with_stamp',
        'default_worker_result_url_with_stamp',
        'default_service_act_url_with_stamp',
        'default_set_application_url_with_stamp',
        'default_set_act_url_with_stamp',
        'default_return_set_act_url_with_stamp',
        'default_upd_url_with_stamp',
        'default_parts_sale_invoice_with_stamp',
        'default_service_center_invoice_with_stamp',
        'default_single_contract_url',
        'default_single_contract_url_with_stamp',
        'default_invoice_contract_url',
        'default_invoice_contract_url_with_stamp',
        'default_contract_url_with_stamp',
        'default_pledge_invoice',
        'default_avito_invoice_url',
        'default_pledge_invoice_with_stamp',
        'default_avito_return_act',
        'default_avito_upd',
        'default_invoice_stamp_url_html',
        'default_invoice_url_html',
        'default_parts_sale_invoice_html',
        'default_service_center_invoice_html',
        'default_parts_sale_invoice_with_stamp_html',
        'default_service_center_invoice_with_stamp_html',
        'default_invoice_contract_url_html',
        'default_invoice_contract_url_with_stamp_html',
        'default_pledge_invoice_html',
        'default_avito_invoice_url_html',
        'default_pledge_invoice_with_stamp_html',
        'default_contract_url_html',
        'default_service_contract_url_html',
        'default_application_url_html',
        'default_disagreement_url_html',
        'default_return_act_url_html',
        'default_acceptance_act_url_html',
        'default_worker_result_url_html',
        'default_service_act_url_html',
        'default_single_act_url_html',
        'default_return_single_act_url_html',
        'default_single_act_services_url_html',
        'default_single_application_url_html',
        'default_service_services_act_html',
        'default_service_return_act_html',
        'default_set_application_url_html',
        'default_set_act_url_html',
        'default_return_set_act_url_html',
        'default_upd_url_html',
        'default_cash_order_html',
        'default_cash_order_stamp_html',
        'default_application_url_with_stamp_html',
        'default_disagreement_url_with_stamp_html',
        'default_return_act_url_with_stamp_html',
        'default_acceptance_act_url_with_stamp_html',
        'default_single_act_url_with_stamp_html',
        'default_return_single_act_url_with_stamp_html',
        'default_single_act_services_url_with_stamp_html',
        'default_single_application_url_with_stamp_html',
        'default_service_return_act_with_stamp_html',
        'default_service_services_act_with_stamp_html',
        'default_worker_result_url_with_stamp_html',
        'default_service_act_url_with_stamp_html',
        'default_set_application_url_with_stamp_html',
        'default_set_act_url_with_stamp_html',
        'default_return_set_act_url_with_stamp_html',
        'default_upd_url_with_stamp_html',
        'default_single_contract_url_html',
        'default_single_contract_url_with_stamp_html',
        'default_contract_url_with_stamp_html',
        'default_avito_return_act_html',
        'default_avito_upd_html',
        'default_order_claims_url',
        'default_order_claims_url_html',
        'default_service_claims_url',
        'default_service_claims_url_html',
        'default_order_claims_url_with_stamp',
        'default_order_claims_url_with_stamp_html',
        'default_service_claims_url_with_stamp',
        'default_service_claims_url_with_stamp_html',
    ];
    const AVAILABLE_TYPES = [
        'legal',
        'individual',
        'person',
    ];

    protected static function boot()
    {
        parent::boot();

        self::deleted(function($model) {
            foreach (self::DOCUMENT_TEMPLATES as $TEMPLATE) {

                if(!$model->{$TEMPLATE}) {
                    continue;
                }
                if(Storage::disk()->exists($model->{$TEMPLATE})) {
                    Storage::disk()->delete($model->{$TEMPLATE});
                }
            }
        });
    }

    function getDefaultDir()
    {
        return "companies/{$this->company_branch->company_id}/branch-{$this->company_branch_id}";
    }

    function setDefaultDocument($path, $type)
    {
        if(!in_array($type, self::DOCUMENT_TEMPLATES)) {
            $error =  ValidationException::withMessages([
                'errors' => ["Некорректный тип документа {$type}"]
            ]);

            throw $error;
        }

        $extension = getFileExtensionFromString($path);

        $uid = uniqid();
        $newPath = ($this->getDefaultDir() . "/{$uid}-{$type}.{$extension}");

        $current = $this->{$type} ?: $newPath;

        if ($path === $current) {
            return $this;
        }

        $tmp_path = config('app.upload_tmp_dir');

        if (!Str::contains($path, [$tmp_path]) || !$path) {
            $this->update([
                $type => ''
            ]);
        } else {
            if(Storage::disk()->exists($current)) {

                Storage::disk()->delete($current);

            }
            $current = $newPath;
            Storage::disk()->move($path, $current);
            $this->update([
                $type => $current
            ]);

            logger($path .' ' . $this->{$type});
        }



        return $this;
    }

    function setDocuments($data)
    {

        $this->fill([
            'name' => $data['name'],
            'type_from' => $data['type_from'],
            'type_to' => $data['type_to'],
           // 'default_contract_url' => $data['default_contract_url'] ?? '',
           // 'default_application_url' => $data['default_application_url']?? '',
           // 'default_return_act_url' => $data['default_return_act_url']?? '',
           // 'default_acceptance_act_url' => $data['default_acceptance_act_url']?? '',
        ]);

        $this->save();

        foreach (self::DOCUMENT_TEMPLATES as $DOCUMENT_TEMPLATE) {
            $this->setDefaultDocument($data[$DOCUMENT_TEMPLATE] ?? null, $DOCUMENT_TEMPLATE);
        }
    }
}
