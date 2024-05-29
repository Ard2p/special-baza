<?php

namespace Modules\PartsWarehouse\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\ContractorOffice\Entities\Vehicle\Price;

class StockItemRequest extends FormRequest
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
            'date' => 'required|date',
            'account_number' => 'required|max:255',
            'account_date' => 'required|date',
            'parts' => 'required|array',
            'parts.*.id' => 'required|exists:warehouse_parts,id',
            'parts.*.stock_id' => 'required|exists:stocks,id',
            'parts.*.unit_id' => 'required|exists:units,id',
            'parts.*.amount' => 'nullable|numeric|min:0',
            'parts.*.cost_per_unit' => 'required|numeric|min:0',
            'parts.*.serial_numbers' => 'nullable|array',
            'parts.*.serial_numbers*.*serial' => 'nullable|array',
            //'serial_accounting' => '',
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
