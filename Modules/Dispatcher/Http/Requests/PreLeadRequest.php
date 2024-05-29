<?php

namespace Modules\Dispatcher\Http\Requests;

use App\Helpers\RequestHelper;
use Illuminate\Foundation\Http\FormRequest;
use Modules\Integrations\Rules\Coordinates;

class PreLeadRequest extends FormRequest
{

    protected function prepareForValidation()
    {
        if ($this->input('customer_type') === 'individual') {
            $this->merge([
                'company_name' => $this->input('contact_person')
            ]);
        }
        if (!$this->input('customer_id')) {
            $this->merge([
                'contact_person' => $this->input('customer.contact_person'),
                'phone'          => $this->input('customer.phone'),
                'requisite'      => $this->input('customer.requisite'),
            ]);
            // $rules['company_name'] = 'required|string|max:255';
        }
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        $rules = [];
        if (!$this->filled('customer_id') && $this->input('customer.type') !== 'unknown') {
            $req = new RequisitesRequest();
            if ($this->input('customer.type') === 'legal') {

                $req->merge([
                    'type' => 'entity'
                ]);
            } else {
                $req->merge(['type' => $this->input('customer.requisite.type')]);
            }
            $this->merge([
                'has_requisite' => $this->input('customer.type')
            ]);
            $rules = array_merge($rules, $req->rules());


            $rules = array_combine(array_map(function ($key) {
                return "requisite.{$key}";
            }, array_keys($rules)), $rules);


        }
        return $rules + [
            'name' => 'nullable|string|max:255',
            'company_name' => 'nullable|string|max:255',//($this->filled('customer_id') && !$this->route('pre_lead')  ? 'nullable' : 'required') . '|string|max:255',
            'contact_person' => 'nullable|string|max:255',
            'customer_id' => /*($this->route('pre_lead') || $this->input('customer_type') === 'existing'  ? 'required' : 'nullable').*/ 'nullable|exists:dispatcher_customers,id',
            'phone' => 'required|numeric|digits:' . RequestHelper::requestDomain()->options['phone_digits'],
            'email' => 'nullable|email|string|max:255',
            'address' => 'nullable|string|max:255',
            'coordinates' => [
                'nullable',
                new Coordinates()
            ],
            'comment' => 'nullable|string|max:500',
            'object_name' => 'nullable|string|max:255',
            'rejected' => 'nullable|string|max:500',
            'positions' => 'required|array|min:1|max:20',
            'positions.*.category_id' => 'required|exists:types,id',
            'positions.*.brand_id' => 'nullable|exists:brands,id',
            'positions.*.machinery_id' => 'nullable|exists:machineries,id',
            'positions.*.comment' => 'nullable|string|max:255',
            'positions.*.attributes.*.id' => 'nullable|exists:optional_attributes,id|max:255',
            'positions.*.attributes.*.value' => 'nullable',
            'positions.*.date_from' => 'nullable|date',
            'positions.*.time_from' => 'nullable|date_format:H:i',
            'positions.*.order_duration' => 'nullable|integer|max:500',
            'positions.*.order_type' => 'nullable|in:shift,hour',
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
