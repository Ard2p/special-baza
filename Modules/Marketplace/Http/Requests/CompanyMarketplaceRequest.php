<?php

namespace Modules\Marketplace\Http\Requests;

use App\Helpers\RequestHelper;
use App\Rules\Inn;
use App\Rules\Kpp;
use Carbon\Carbon;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Modules\CorpCustomer\Entities\InternationalLegalDetails;
use Modules\Integrations\Rules\Coordinates;
use Modules\Orders\Entities\Payment;

class CompanyMarketplaceRequest extends FormRequest
{

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */

    public function attributes()
    {
        return [
            'contact_person' => trans('transbaza_register_order.contact_person'),
            'vehicles.*.start_time' => trans('transbaza_register_order.start_time')];
    }

    function messages()
    {
        return [
            'phone.required' => trans('transbaza_register_order.validate_phone'),
            'contact_person.required' => trans('transbaza_register_order.validate_person'),
            'vehicles.*.start_time.required' => trans('transbaza_register_order.validate_time'),
        ];
    }

    function prepareForValidation()
    {
        $request = $this;

        $request->merge([
            'phone' => trimPhone($request->phone)
        ]);
    }


    public function rules()
    {

        $international_domain = RequestHelper::requestDomain()->alias !== 'ru';

        $after_date = now()->startOfDay()->format('Y-m-d');

        $rules = [
            'coordinates' => new Coordinates,
            'address' => 'required|string|min:5',
            'promo_code' => 'nullable|in:' . $this->test_promo,
            'contact_person' => 'required|string|min:2',
            'vehicles' => 'required|array',
            'vehicles.*.id' => 'required|exists:machineries,id',
            'vehicles.*.date_from' => 'required|date|after_or_equal:' . $after_date,
            'vehicles.*.start_time' => 'required|date_format:H:i',
            'vehicles.*.order_duration' => 'required|integer|max:60',
            'vehicles.*.order_type' => 'required|in:shift,hour',
        ];
        if (!Auth::guard('api')->check()) {
            $rules = array_merge($rules, [
                'email' => 'required|email|unique:users',
                'phone' => 'required|digits:' . RequestHelper::requestDomain()->options['phone_digits'] . '|unique:users',
            ]);
        }else {
            $rules['phone'] = 'required|digits:' . RequestHelper::requestDomain()->options['phone_digits'] . '|unique:users,id,' . Auth::guard('api')->user()->id;
            $rules['company_branch_id'] = 'nullable|required_without:customer_id|exists:company_branches,id';
            $rules['customer_id'] = 'nullable|required_without:company_branch_id';
        }


            $rules = array_merge($rules, [
                'company.name' => 'required|string|max:255',
                'company.type' => 'required|in:legal,individual',
            ]);

            if ($this->input('company.type') === 'legal') {

                if($international_domain) {
               //     unset($rules['company.name']);
                }
                $rules = /*$international_domain
                    ? array_merge($rules, array_combine(array_map(function ($key) {
                        return "company.{$key}";
                    }, array_keys(InternationalLegalDetails::getValidationRules())), InternationalLegalDetails::getValidationRules()))

                    : */array_merge($rules, ($this->filled('customer_id') ? [] :
                        ['company.inn' =>
                            ['required',
                                !$international_domain ? (new Inn()) : 'max:255'
                            ]/*,
                            'company.kpp' => [
                                'required',
                                new Kpp()
                            ]*/,]));
            }


        return $rules;
    }

    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return !Auth::guard('api')->check() ?: Auth::guard('api')->check();
    }
}
