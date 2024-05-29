<?php

namespace Modules\Dispatcher\Http\Requests;

use App\Helpers\RequestHelper;
use Illuminate\Foundation\Http\FormRequest;
use Modules\Integrations\Rules\Coordinates;

class PreLeadTransformRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'name' => 'nullable|string|max:255',
            'contact_person' => 'required|string|max:255',
            'company_name' => $this->input('customer_type') === 'new' ? 'required' : 'nullable' . '|string|max:255',
            'customer_id' => ($this->input('customer_type') === 'existing' ? 'required' : 'nullable' . '|exists:dispatcher_customers,id'),
            'phone' => 'required|numeric|digits:' . RequestHelper::requestDomain()->options['phone_digits'],
            'email' => 'nullable|email|string|max:255',
            'address' => 'required|string|max:255',
            'coordinates' => [
                'required',
                new Coordinates()
            ],
            'comment' => 'nullable|string|max:500',
            'object_name' => 'nullable|string|max:500',
            'positions' => 'required|array|min:1|max:20',
            'positions.*.category_id' => 'required|exists:types,id',
            'positions.*.brand_id' => 'nullable|exists:brands,id',
            'positions.*.machinery_id' => 'nullable|exists:machineries,id',
            'positions.*.comment' => 'nullable|string|max:255',
            'positions.*.order_duration' => 'required|integer|max:500',
            'positions.*.order_type' => 'required|in:shift,hour',
            'positions.*.date_from' => 'required|date',
            'positions.*.time_from' => 'required|date_format:H:i',
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
