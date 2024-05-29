<?php

namespace Modules\Orders\Http\Requests;

use App\Helpers\RequestHelper;
use App\Rules\Inn;
use App\Rules\Kpp;
use Carbon\Carbon;
use http\Exception\InvalidArgumentException;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Modules\CorpCustomer\Entities\InternationalLegalDetails;
use Modules\Integrations\Rules\Coordinates;
use Modules\Orders\Entities\Payment;

class OrderRequest extends FormRequest
{

    private $test_promo = 'DEMO_KINOSK';

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */

    public function attributes()
    {
        return [
            'contact_person' => trans('transbaza_register_order.contact_person'),
            'start_time' => trans('transbaza_register_order.start_time')];
    }

    function messages()
    {
        return [
            'phone.required' => trans('transbaza_register_order.validate_phone'),
            'contact_person.required' => trans('transbaza_register_order.validate_person'),
            'start_time.required' => trans('transbaza_register_order.validate_time'),
        ];
    }

    function prepareForValidation()
    {
        $request = $this;
        try {
            $date_from = Carbon::parse($request->date_from);
        } catch (\Exception $e) {
            throw new \InvalidArgumentException();
        }


        $request->merge([
            'date_from' => $date_from->format('Y-m-d'),
            'phone' => trimPhone($request->phone)
        ]);
    }


    public function rules()
    {

        $international_domain = RequestHelper::requestDomain()->alias !== 'ru';

        $after_date = now()->addDay()->startOfDay()->format('Y-m-d');

        $rules = [
            'pay_type' => 'required|in:' . trim(implode(Payment::getSystems(), ','), ','),
            'type' => 'required|in:shift,hour',
            'coordinates' => new Coordinates,
            'address' => 'required|string|min:5',
            'duration' => 'required|min:1',
            'promo_code' => 'nullable|in:' . $this->test_promo,
            'contact_person' => 'required|string|min:2',
            'order_vehicles' => 'required|array',
            'order_vehicles.*.id' => 'required|integer',
            'start_time' => 'required|date_format:H:i',
            'date_from' => 'required|date|after_or_equal:' . $after_date,
            /*'date_from' => 'required|date|date_format:Y-m-d H:i|after:' . now()->addDay()->format('Y-m-d'),
            'date_to' => 'required|date|date_format:Y-m-d H:i|after:' . $request->date_from,*/
        ];
        if (!Auth::guard('api')->check()) {
            $rules = array_merge($rules, [
                'email' => 'required|email|unique:users',
                'phone' => 'required|digits:' . RequestHelper::requestDomain()->options['phone_digits'] . '|unique:users',
            ]);
        }else {
            $rules['phone'] = 'required|digits:' . RequestHelper::requestDomain()->options['phone_digits'] . '|unique:users,id,' . Auth::guard('api')->user()->id;
        }

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
        return !Auth::guard('api')->check() ?: Auth::guard('api')->check();
    }
}
