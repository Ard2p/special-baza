<?php

namespace Modules\Dispatcher\Http\Requests;

use App\Helpers\RequestHelper;
use App\Machines\Type;
use Carbon\Carbon;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\CompanyOffice\Entities\Company\Contact;
use Modules\CompanyOffice\Services\ContactsService;
use Modules\ContractorOffice\Entities\System\Tariff;
use Modules\ContractorOffice\Entities\Vehicle\Price;
use Modules\Integrations\Rules\Coordinates;
use Modules\RestApi\Entities\KnowledgeBase\Category;

class CreateLeadRequest extends FormRequest
{

    function prepareForValidation()
    {
        if($this->input('customer_type') === 'individual') {
            $this->merge([
                'company_name' => $this->input('contact_person')
            ]);
        }

        if(!$this->input('customer_id')) {
            $this->merge([
                'contact_person' => $this->input('customer.contact_person'),
                'phone' => $this->input('customer.phone'),
                'requisite' => $this->input('customer.requisite'),
            ]);
            // $rules['company_name'] = 'required|string|max:255';
        }
        try {
            $this->merge([
                'phone' => trimPhone($this->phone),
            ]);
            $vehicles = $this->input('vehicles_categories');

            foreach ($vehicles as &$vehicle) {

                $category = Type::query()->find($vehicle['id']);

                if($category->tariffs->isNotEmpty()) {

                    $vehicle['order_type'] = $category->tariff->type;

                    $vehicle['order_duration'] = 1;

                    if( !$vehicle['coordinates']) {
                        $vehicle['coordinates'] = 'error';
                        $vehicle['waypoint'] = 'error';
                    }

                }else {
                /*    if($vehicle['order_type'] === 'distance') {
                        $vehicle['order_type'] = '';
                    }*/
                    unset($vehicle['coordinates'], $vehicle['waypoint'], $vehicle['params']);
                }
            }
            $this->merge([
                'vehicles_categories' => $vehicles
            ]);
            if ($this->filled('contacts')) {
                $contacts = $this->input('contacts');
                foreach ($contacts as &$contact) {
                    $contact['phone'] = trimPhone($contact['phone'] ?? '');
                }
                $this->merge([
                    'contacts' => $contacts
                ]);
            }
        } catch (\Exception $exception) {

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
            }else {
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
        $client = toBool($this->input('client'));
        $rules = array_merge($rules, [
            'contact_person' => 'required|string|max:255',
            'phone' => 'required|numeric|digits:' . RequestHelper::requestDomain()->options['phone_digits'],
            'email' => 'nullable|email',
            'title' => 'required|string|max:255',
            'publish_type' => 'required|in:my_proposals,all_contractors,for_companies',
            'address' => 'required|string|max:255',
            'comment' => 'nullable|string|max:500',
            'object_name' => 'nullable|string|max:250',
            'status' => 'required|string',
            'creator_id' => 'required|exists:users,id',

            'contractor_requisite_id' => 'required|string',
            'documents_pack_id' => 'required|exists:company_documents_packs,id',

            'pay_type' => 'required|in:cash,cashless',// . implode(',', Price::getTypes()),
            'region_id' => [
                'required',
                Rule::exists('regions', 'id')->where('country_id', RequestHelper::requestDomain()->country->id)
            ],
            //'order_type' => 'required|in:shift,hour',
            'client' => 'required|boolean',
            //'duration' => 'required|integer|min:1|max:' . ($this->input('order_type') === 'shift' ? 24 : 8),
            'city_id' => [
                'required',
                Rule::exists('cities', 'id')->where('region_id', $this->region_id)
            ],
            'vehicles_categories' => 'nullable|array',
            'vehicles_categories.*.id' => 'required|exists:types,id',
            'vehicles_categories.*.order_duration' => 'required|integer|min:1|max:360',
            'vehicles_categories.*.order_type' => 'required|in:shift,hour,' . implode(',', Tariff::getTariffs()),
            'vehicles_categories.*.waypoint' => 'nullable|string|min:8|max:255',
            'vehicles_categories.*.params' => 'nullable|array',
            'vehicles_categories.*.params.*' => [
                function ($attribute, $value, $fail) {

                    $id = last(explode('.', $attribute));


                    if($id !== 'concrete' || (!is_numeric($value) || $value <= 0)) {
                        $fail('Некорректный параметр');
                    }
                },
            ],
            'vehicles_categories.*.coordinates' => [
                'nullable',
                new Coordinates()
            ],
            'vehicles_categories.*.date_from' => 'required|date|after:' . now()->subYear()->format('Y-m-d H:i:s'),
            'vehicles_categories.*.start_time' => 'required|date_format:H:i',

            'vehicles_categories.*.count' => 'required|integer|min:1|max:10',
            'coordinates' => [
                'required',
                new Coordinates()]
        ]);
        if (!$client) {
            $rules['customer_id'] = 'nullable|exists:dispatcher_customers,id';

        }
        $rules['contract_id'] = 'nullable|exists:dispatcher_customer_contracts,id';

        return $rules + ContactsService::getValidationRules();
    }

    function attributes()
    {
        return [
            'contact_person' => trans('transbaza_register_order.contact_person'),
            'vehicles.*.start_time' =>  '',//rans('transbaza_register_order.start_time'),
            'vehicles_categories.*.date_from' =>'',// trans('transbaza_calendar.date_from'),
            'vehicles_categories.*.id' =>'',
            'vehicles_categories.*.order_duration' =>'',
            'vehicles_categories.*.order_type' =>'',
            'region_id' =>'',
            'city_id' =>'',
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
