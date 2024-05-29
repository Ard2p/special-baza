<?php

namespace Modules\ContractorOffice\Http\Requests\Shop;

use Illuminate\Foundation\Http\FormRequest;
use Modules\ContractorOffice\Entities\Vehicle\Price;

class MachineryPurchaseRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'provider_id' => 'required|int',
            'pay_type' => 'required|in:' . implode(',', Price::getTypes()),
            'account_number' => 'required|max:255',
            'account_date' => 'required|date|max:255',
            'currency' => 'required|in:rub,eur,usd',

            'machineries' => 'required|array',

            'machineries.*.cost' => 'required|numeric|min:0|max:99999999999',
            'machineries.*.engine_hours' => 'required|numeric|min:0|max:99999999999',
            'machineries.*.type' => 'required|in:new,used',
            'machineries.*.vin' => 'nullable|string|max:255',
            'machineries.*.brand_id' => 'required|exists:brands,id',
            'machineries.*.category_id' => 'required|exists:types,id',
            'machineries.*.model_id' => 'required|exists:machinery_models,id',
            'machineries.*.name' => 'nullable|string|max:255',
            'machineries.*.serial_number' => 'nullable|string|max:255',
            'machineries.*.licence_plate' => 'nullable|string|max:255',
            'machineries.*.board_number' => 'nullable|string|max:255',
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
