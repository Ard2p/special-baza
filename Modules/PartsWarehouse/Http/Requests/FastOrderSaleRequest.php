<?php

namespace Modules\PartsWarehouse\Http\Requests;

use App\Helpers\RequestHelper;
use App\Service\RequestBranch;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Dispatcher\Http\Requests\RequisitesRequest;

class FastOrderSaleRequest extends FormRequest
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
        $rules = array_merge([
            'phone'                     => 'required|numeric|digits:' . RequestHelper::requestDomain()->options['phone_digits'],
            'date'                      => 'required|date',
            'email'                     => 'nullable|email',
            'title'                     => 'required|string|max:255',
            'base_id'                   => 'required|numeric',
            'documents_pack_id'         => 'required|numeric',
            'positions'                 => 'required|array|min:1',
            'positions.*.id'            => [
                'required',
                'distinct',
                Rule::exists('stock_items', 'id')->where('company_branch_id', app(RequestBranch::class)->companyBranch->id)
            ],
            'positions.*.amount'        => 'required|numeric|min:1',
            'positions.*.serials'       => 'nullable|array',
            'positions.*.cost_per_unit' => 'required|numeric|min:0',
        ], $rules);
        return $rules;
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
