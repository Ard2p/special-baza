<?php

namespace Modules\Dispatcher\Http\Requests;

use App\Helpers\RequestHelper;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreateContractorRequest extends FormRequest
{

    protected function prepareForValidation()
    {

    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {

        $rules = [];

        if (is_array($this->input('requisite')) && !empty(array_filter($this->input('requisite')))) {
            $req = new RequisitesRequest();

            if ($this->input('type') === 'legal') {

                $req->merge([
                    'type' => 'entity'
                ]);
            }else {
                $req->merge([
                    'type' =>  $this->input('type')
                ]);
            }
            $this->merge([
                'has_requisite' => $this->input('type')
            ]);
            $rules = array_merge($rules, $req->rules());


            $rules = array_combine(array_map(function ($key) {
                return "requisite.{$key}";
            }, array_keys($rules)), $rules);


        }

        return array_merge($rules, [
            'company_name' => 'required|string|max:255',
            'address' => 'nullable|string|max:255',
            'contact_person' => 'required|string|max:255',
            'phone' => 'required|numeric|digits:' .  RequestHelper::requestDomain()->options['phone_digits'],
            'region_id' => 'required|integer|exists:regions,id',
            'city_id' => [
                'required',
                Rule::exists('cities', 'id')->where('region_id', $this->input('region_id'))
            ],
        ]);
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
