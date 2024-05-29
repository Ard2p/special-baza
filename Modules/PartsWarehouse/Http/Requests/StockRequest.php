<?php

namespace Modules\PartsWarehouse\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\Integrations\Rules\Coordinates;

class StockRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'name' => 'required|string|max:255',
            'address' => 'required|string|max:255',
            'coordinates' => [
                'nullable',
                new Coordinates()
            ],
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
