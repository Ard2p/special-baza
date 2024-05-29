<?php

namespace Modules\CompanyOffice\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class BranchSettingsRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'default_contract_name' => 'required|string|max:255',
            'default_contract_url' => 'nullable|string|max:255',
            'contract_number_template' => 'required|string|max:255',
            'default_contract_prefix' => 'nullable|string|max:255',
            'default_contract_postfix' => 'nullable|string|max:255',

            'machinery_document_mask' => 'nullable|string|max:255',
            'contract_service_number_template' => 'nullable|string|max:255',
            'contract_service_default_contract_prefix' => 'nullable|string|max:255',
            'contract_service_default_contract_postfix' => 'nullable|string|max:255',

            'default_machinery_sale_contract_name' => 'nullable|string|max:255',
            'default_machinery_sale_contract_prefix' => 'nullable|string|max:255',
            'default_machinery_sale_contract_postfix' => 'nullable|string|max:255',
        ];
    }

    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }
}
