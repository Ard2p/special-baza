<?php

namespace Modules\ContractorOffice\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class MachinerySetRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'name'                         => 'required|string|max:255',
            'equipments'                   => 'required|array',
            'equipments.*.category_id'     => 'required|exists:types,id',
            'equipments.*.brand_id'        => 'nullable|exists:brands,id',
            'equipments.*.model_id'        => 'nullable|exists:machinery_models,id',
            'equipments.*.count'           => 'required|numeric|min:1',
            'equipments.*.parts'           => 'nullable|array',
            'equipments.*.parts.*.count'   => 'required|numeric|min:1',
            'equipments.*.parts.*.part_id' => 'required|exists:warehouse_parts,id',
            'prices.delivery_cost'         => 'required|numeric|min:0',
            'prices.return_delivery_cost'  => 'required|numeric|min:0',
            'prices.cash'                  => 'required|numeric|min:0',
            'prices.cashless_without_vat'  => 'required|numeric|min:0',
            'prices.cashless_vat'          => 'required|numeric|min:0',
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
