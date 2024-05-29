<?php

namespace Modules\AdminOffice\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class WarehousePartRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'name' => 'required|',
            'vendor_code' => 'required|string|max:255',
            'brand_id' => 'required|exists:brands,id',
            'group_id' => 'required|exists:warehouse_parts_groups,id',
            'unit_id' => 'required|exists:units,id',
            'images' => 'nullable|array',
         //  'models' => 'required|array',
         //  'models.*.model_id' => 'required|exists:machinery_models,id',
         //  'models.*.serial_numbers' => 'nullable|array',
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
