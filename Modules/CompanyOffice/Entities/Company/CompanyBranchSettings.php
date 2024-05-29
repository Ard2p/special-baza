<?php

namespace Modules\CompanyOffice\Entities\Company;

use App\Overrides\Model;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Modules\CompanyOffice\Services\BelongsToCompanyBranch;

class CompanyBranchSettings extends Model
{

    use BelongsToCompanyBranch;

    public $timestamps = false;

    protected $primaryKey = 'company_branch_id';

    protected $fillable = [
        'company_branch_id',
        'default_contract_name',
        'default_contract_prefix',
        'default_contract_postfix',
        'price_without_vat',
        'documents_head_image',
        'default_machinery_sale_contract_name',
        'default_machinery_sale_contract_prefix',
        'default_machinery_sale_contract_postfix',

        'default_contract_url',
      //  'default_service_contract_url',
        'default_machinery_sale_contract_url',
        'default_machinery_sale_application_url',
        'default_commercial_offer_url',
        'default_application_url',
        'default_disagreement_url',
        'default_return_act_url',
        'default_acceptance_act_url',
        'default_cash_order',
        'default_single_act_url',
        'default_single_application_url',
      //  'default_service_return_act',
        'contract_number_template',
        'ya_disk_oauth',
        'contract_service_number_template',
        'machinery_document_mask',
        'contract_service_default_contract_prefix',
        'contract_service_default_contract_postfix',
        'use_shift_settings',
        'shift_settings',
        'split_invoice_by_month',
    ];

    protected $casts = [
        'price_without_vat' => 'bool',
        'use_shift_settings' => 'bool',
        'split_invoice_by_month' => 'bool',
        'shift_settings' => 'object',
    ];

    const DOCUMENT_TEMPLATES = [
        'default_contract_url',
        //'default_service_contract_url',
        'default_machinery_sale_contract_url',
        'default_machinery_sale_application_url',
        'default_commercial_offer_url',
        'default_application_url',
        'default_disagreement_url',
        'default_return_act_url',
        'default_acceptance_act_url',
       // 'default_single_act_url',
     //   'default_single_application_url',
       // 'default_service_return_act',
      // 'default_cash_order',
    ];

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
        $this->fillable += self::DOCUMENT_TEMPLATES;
    }



    function getDefaultDir()
    {
        return "companies/{$this->company_branch->company_id}/branch-{$this->company_branch->id}";
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

    function setDefaultDocumentImage($path)
    {
        $extension = getFileExtensionFromString($path);

        $current = $this->documents_head_image ?: ($this->getDefaultDir() . "/default_document_image.{$extension}");

        if ($path === $current) {
            return $this;
        }

        $tmp_path = config('app.upload_tmp_dir');

        if (!Str::contains($path, [$tmp_path]) || !$path) {
            $this->update([
                'documents_head_image' => ''
            ]);
        } else {
            if(Storage::disk()->exists($current)) {
                Storage::disk()->delete($current);
            }
            Storage::disk()->move($path, $current);
            $this->update([
                'documents_head_image' => $current
            ]);
        }

        return $this;
    }

    function getActualShiftName($duration, $isMonth = false)
    {
        if($isMonth) {
            return  'Месяц';
        }
        $duration = (int) $duration;
        if($duration === 24) {
            return  'Сутки';
        }
        if($this->use_shift_settings && $this->shift_settings->day_length == $duration) {
            return $this->shift_settings->day_name;
        }

        return  trans('units.shift');
    }

}
