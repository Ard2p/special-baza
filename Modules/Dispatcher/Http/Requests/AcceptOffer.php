<?php

namespace Modules\Dispatcher\Http\Requests;

use App\Helpers\RequestHelper;
use App\Rules\Inn;
use App\Rules\Kpp;
use Illuminate\Foundation\Http\FormRequest;
use Modules\CorpCustomer\Entities\InternationalLegalDetails;
use Modules\Orders\Entities\Payment;

class AcceptOffer extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        $international_domain = RequestHelper::requestDomain()->alias !== 'ru';

        $rules =  [
            'offer_id' => 'exists:lead_offers,id',
            'pay_type' => 'required|in:' . trim(implode(Payment::getSystems(), ','), ','),
        ];

        if ($this->input('pay_type') === 'invoice') {
            $rules = array_merge($rules, [
                'invoice.name' => 'required|string|max:255',
                'invoice.type' => 'required|in:entity,individual',
            ]);

            if ($this->input('invoice.type') === 'entity') {

                if($international_domain) {
                    unset($rules['invoice.name']);
                }
                $rules = $international_domain
                    ? array_merge($rules, array_combine(array_map(function ($key) {
                        return "invoice.{$key}";
                    }, array_keys(InternationalLegalDetails::getValidationRules())), InternationalLegalDetails::getValidationRules()))

                    : array_merge($rules,
                        ['invoice.inn' =>
                            ['required',
                                new Inn()
                            ],
                            'invoice.kpp' => [
                                'required',
                                new Kpp()
                            ],]);
            }
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
        return true;
    }
}
