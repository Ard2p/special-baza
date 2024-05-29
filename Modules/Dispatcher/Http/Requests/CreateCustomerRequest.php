<?php

namespace Modules\Dispatcher\Http\Requests;

use App\Helpers\RequestHelper;
use App\Service\RequestBranch;
use App\User\IndividualRequisite;
use Carbon\Carbon;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\CompanyOffice\Services\ContactsService;

class CreateCustomerRequest extends FormRequest
{

    protected function prepareForValidation()
    {

        if($this->filled('requisite.birth_date')) {
            try {
                $this->merge(
                    ['requisite' => array_merge(
                        $this->input('requisite'),
                        ['birth_date' => Carbon::parse($this->input('requisite.birth_date'))]
                )]);
            }catch (\Exception $exception) {

            }
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

        if (is_array($this->input('requisite'))) {
            $req = new RequisitesRequest($this->query->all(), $this->request->all());
            if ($this->input('type') === 'legal') {

                $req->merge([
                    'type' => 'entity'
                ]);
            }else {
                $req->merge(['type' => $this->input('type')]);
            }
            $this->merge([
                'has_requisite' => $this->input('type')
            ]);
            $rules = array_merge($rules, $req->rules());


            $rules = array_combine(array_map(function ($key) {
                return "requisite.{$key}";
            }, array_keys($rules)), $rules);


        }

        return array_merge($rules, ([
            'company_name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('dispatcher_customers')
                    ->where(function ($q) {
                        $q->where('company_branch_id', app()->make(RequestBranch::class)->companyBranch->id);
                        if($this->route('customer')) {
                            $q->where('id', '!=', $this->route('customer'));
                        }

                    })
            ],
            'contractor_requisite_id' => 'sometimes',
            'address' => 'nullable|string|max:255',
            'contact_person' => 'nullable|string|max:255',
            'phone' => 'required|numeric|digits:' . RequestHelper::requestDomain()->options['phone_digits'],
            'email' => 'nullable|email',
            'region_id' => 'required|integer|exists:regions,id',
            'city_id' => [
                'required',
                Rule::exists('cities', 'id')->where('region_id', $this->input('region_id'))
            ],
        ]  + (($this->input('type') === IndividualRequisite::TYPE_UNKNOWN ||  !$this->input('type')) ? [] : ContactsService::getValidationRules())) );
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
