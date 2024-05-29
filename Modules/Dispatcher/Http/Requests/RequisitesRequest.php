<?php

namespace Modules\Dispatcher\Http\Requests;

use App\Helpers\RequestHelper;
use App\Rules\Inn;
use App\Rules\Kpp;
use App\Rules\Ogrn;
use App\Service\RequestBranch;
use App\User\EntityRequisite;
use App\User\IndividualRequisite;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\CorpCustomer\Entities\InternationalLegalDetails;

class RequisitesRequest extends FormRequest
{

    public function attributes()
    {
        return $this->input('type') === 'entity'
            ? EntityRequisite::$attributesName
            : IndividualRequisite::$attributesName;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        $isNotRf = app(RequestBranch::class)?->companyBranch?->is_not_rf;

        return  $this->input('type') === 'entity'
            ? (RequestHelper::requestDomain()->alias === 'ru' ? [
                    'name'                   => 'required|string|max:255',
                    'actual_address'         => 'nullable|string|max:255',
                    'short_name'             => 'nullable|string|max:255',
                    'inn'                    => $isNotRf ? 'required' : [
                        'required',
                         new Inn(),
                    //    Rule::unique(EntityRequisite::class, 'inn')
                    //        ->where('company_branch_id', app(RequestBranch::class)?->companyBranch->id)
                    //        ->ignore(request('requisite.id'))
                    ],
                    'kpp'                    =>  $isNotRf ? 'nullable' : [
                        'nullable',
                        new Kpp(),
                    ],
                    'ogrn'                   =>  $isNotRf ? 'nullable' : [
                        'nullable',
                        new Ogrn(),
                    ],
                    'email'                  => 'nullable|email',
                    'director'               => 'nullable|string|max:255',
                    'booker'                 => 'nullable|string|max:255',
                    'bank_requisites.*.name' => 'required|string|max:255',
                    'bank_requisites.*.bik'  => $isNotRf ? 'nullable':'nullable|digits:9',
                    'bank_requisites.*.ks'   => $isNotRf ? 'nullable':'nullable|string|digits:20',
                    'bank_requisites.*.rs'   => $isNotRf ? 'nullable':'nullable|string|digits:20',

                    'status'            => 'nullable',
                    'register_address'  => 'nullable|string|max:255',
                    'director_short'    => 'nullable|string|max:255',
                    'director_genitive' => 'nullable|string|max:255',
                    'charter'           => 'nullable|string|max:255',
                    'director_position' => 'nullable|string|max:255',
                    'vat_system'        => 'nullable|in:cashless_without_vat,cashless_vat',
            ] : InternationalLegalDetails::getValidationRules())
            : IndividualRequisite::getRulesByType($this->input('type'));
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
