<?php

namespace Modules\Orders\Http\Requests;

use App\Helpers\RequestHelper;
use App\Rules\Inn;
use App\Rules\Kpp;
use App\Service\RequestBranch;
use Carbon\Carbon;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Modules\ContractorOffice\Entities\System\Tariff;
use Modules\CorpCustomer\Entities\InternationalLegalDetails;
use Modules\Integrations\Rules\Coordinates;
use Modules\Orders\Entities\Payment;

class PaymentRequest extends FormRequest
{

    private $test_promo = 'DEMO_KINOSK';


    protected function prepareForValidation()
    {

        $this->merge([
            'phone' => trimPhone($this->input('phone'))

        ]);

        try {
            $date_from = Carbon::parse($this->input('date_from'));

            $this->merge([
                'date_from' => (string)$date_from
            ]);

        }catch (\Exception $exception) {

        }
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        $international_domain = RequestHelper::requestDomain()->alias !== 'ru';

        $after_date = now()->addDay()->startOfDay()->format('Y-m-d');

        $rules = [
            'pay_type' => 'required|in:' . trim(implode(Payment::getSystems(), ','), ','),
            'coordinates' => new Coordinates,
            'address' => 'required|string|min:5',
            'promo_code' => 'nullable|in:' . $this->test_promo,
            'contact_person' => 'required|string|min:2',
            'lead_id' => 'nullable|integer',
            'contractor_id' => 'required|exists:company_branches,id',
            'vehicles' => 'required|array',
            'vehicles.*.id' => 'required|exists:machineries,id',
            'vehicles.*.order_type' => 'required|in:hour,shift',
            'vehicles.*.order_duration' => 'required|numeric|min:1|max:24',
            'vehicles.*.date_from' => 'required|date|after_or_equal:' . $after_date,
            'vehicles.*.order_params' => 'nullable|array',
            'vehicles.*.order_params.*' => [
                function ($attribute, $value, $fail) {

                    $id = last(explode('.', $attribute));

                    if($id !== 'concrete' || !is_numeric($value)) {
                        $fail('Некорректный параметр');
                    }
                },
            ],
            'vehicles.*.order_waypoints' => [
                'nullable',
                function ($attribute, $value, $fail) {

                    $id = (explode('.', $attribute))[1];

                    $exists = DB::table('machineries')
                        ->whereIn('tariff_type', [Tariff::TIME_CALCULATION, Tariff::CONCRETE_MIXER])
                        ->where('id', '=', $this->input("vehicles.{$id}.id"))
                        ->exists();

                    if(!$exists && !$value) {
                        $fail('Некорректный адрес');
                    }
                },
                'array',

            ],
            'vehicles.*.order_waypoints.coordinates' => [
                'nullable',
                new Coordinates()
            ],
        ];
        if(!app()->make(RequestBranch::class)->companyBranch) {
            $rules['company_branch_id'] = 'required|required|exists:company_branches,id';
        }
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
