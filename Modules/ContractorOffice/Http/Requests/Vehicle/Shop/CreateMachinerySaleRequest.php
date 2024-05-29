<?php

namespace Modules\ContractorOffice\Http\Requests\Vehicle\Shop;

use App\Helpers\RequestHelper;
use Illuminate\Foundation\Http\FormRequest;
use Modules\ContractorOffice\Entities\Vehicle\Price;

class CreateMachinerySaleRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'date' => 'required|date',
            'customer_id' => 'nullable|exists:dispatcher_customers,id',
            'phone' => 'required|numeric|digits:' . RequestHelper::requestDomain()->options['phone_digits'],
            'pay_type' => 'required|in:' . implode(',', Price::getTypes()),
            'email' => 'nullable|email|max:255',
            'contact_person' => 'required|string|max:255',

            'positions' => 'required|array',
            'positions.*.brand_id' => 'nullable|exists:brands,id',
            'positions.*.category_id' => 'required|exists:types,id',
            'positions.*.machinery_model_id' => 'nullable|exists:machinery_models,id',
            'positions.*.amount' => 'required|integer|min:1',
            'positions.*.comment' => 'nullable|string|max:255',
            'positions.*.year' => 'nullable|integer',
            'positions.*.engine_hours' => 'nullable|numeric|min:0',

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
