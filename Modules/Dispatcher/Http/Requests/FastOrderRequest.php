<?php

namespace Modules\Dispatcher\Http\Requests;

use App\Helpers\RequestHelper;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\CompanyOffice\Services\ContactsService;
use Modules\ContractorOffice\Entities\CompanyWorker;
use Modules\ContractorOffice\Entities\System\Tariff;
use Modules\ContractorOffice\Entities\Vehicle\Price;
use Modules\Integrations\Rules\Coordinates;

class FastOrderRequest extends FormRequest
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
        $rules = array_merge($rules, [
            'machinery_base_id' => 'required|exists:machinery_bases,id',
            'external_id'       => 'nullable|string|max:255',
            'contact_person'    => 'required|string|max:255',
            'phone'             => 'required|numeric|digits:' .RequestHelper::requestDomain()->options['phone_digits'],
            'email'             => 'nullable|email',
            'title'             => 'required|string|max:255',
            'publish_type'      => 'required|in:my_proposals,all_contractors,for_companies',
            'address'           => 'required|string|max:255',
            'comment'           => 'nullable|string|max:500',
            'status'            => 'required|string',
            'channel'            => [$this->filled('customer_id') ? 'nullable' : 'required','string'],
            'source'            => [$this->filled('customer_id') ? 'nullable' : 'required','string'],
            'machinery_set_id'  => 'nullable|exists:machinery_sets,id',
            //'creator_id' => 'required|exists:users,id',

            'contractor_requisite_id' => 'required|string',
            'documents_pack_id'       => 'required|exists:company_documents_packs,id',

            'pay_type'                     => 'required|in:cash,cashless',// . implode(',', Price::getTypes()),
            'region_id'                    => [
                'required',
                Rule::exists('regions', 'id')->where('country_id',RequestHelper::requestDomain()->country->id)
            ],
            //'order_type' => 'required|in:shift,hour',
            'client'                       => 'required|boolean',
            //'duration' => 'required|integer|min:1|max:' . ($this->input('order_type') === 'shift' ? 24 : 8),
            'city_id'                      => [
                'required',
                Rule::exists('cities', 'id')->where('region_id', $this->region_id)
            ],
            'vehicles'                     => [
                (is_array($this->input('sets')) && count($this->input('sets')) > 0) || (is_array($this->input('warehouse_sets')) && count($this->input('warehouse_sets')) > 0)
                    ? 'nullable'
                    : 'required'
            ],
            'vehicles.*.id'                => 'required|exists:machineries,id',
            'vehicles.*.order_duration'    => 'required|integer|min:1|max:500',
            'vehicles.*.order_type'        => 'required|in:shift,hour,' . implode(',', Tariff::getTariffs()),
            /*  'vehicles.*.coordinates' => [
                  'required',
                  new Coordinates()
              ],*/
            'vehicles.*.date_from'         => 'required|date|after:' . now()->subYear()->format('Y-m-d H:i:s'),
            'coordinates'                  => [
                'required',
                new Coordinates()
            ],
            'vehicles.*.type'              => 'required|in:contractor,vehicle',
            'vehicles.*.cost_per_unit'     => 'required|numeric|min:0|max:99999999',
            'vehicles.*.cashless_type'     => 'nullable|in:' . implode(',', Price::getCashlessTypes()),
            'vehicles.*.value_added'       => 'required|numeric|min:0|max:99999999',
            'vehicles.*.delivery_cost'     => 'nullable|numeric|min:0|max:99999999',
            'vehicles.*.return_delivery'   => 'nullable|numeric|min:0|max:99999999',
            'vehicles.*.comment'           => 'nullable|string|max:500',
            'vehicles.*.driver_type'       => 'required|in:warm,cold',
            'vehicles.*.company_worker_id' => [
                function (
                    $attribute,
                    $value,
                    $fail) {

                    $id = (explode('.', $attribute))[1];
                    if ($this->input("cart.{$id}.company_worker_id") && !CompanyWorker::query()->forBranch()->find($value)) {
                        $fail('Выберите водителя');
                    }
                },
            ],
        ]);

        $rules['customer_id'] = 'nullable|exists:dispatcher_customers,id';

        /*            if(!$this->input('customer_id')) {
                        $rules['company_name'] = 'required|string|max:255';
                    }*/


        return $rules + ContactsService::getValidationRules();
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
