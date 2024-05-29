<?php

namespace Modules\Orders\Http\Requests\ServiceCenter;

use App\Helpers\RequestHelper;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Modules\CompanyOffice\Services\ContactsService;
use Modules\ContractorOffice\Entities\System\Tariff;
use Modules\Dispatcher\Http\Requests\RequisitesRequest;
use Modules\Integrations\Rules\Coordinates;
use Modules\Orders\Entities\Service\ServiceCenter;

class ServiceRequest extends FormRequest
{

    function prepareForValidation()
    {
        if($this->input('type') === 'in') {
            $this->merge([
                'customer_id' => null
            ]);
        }
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
        if($this->input('type') === 'inner') {
            $this->merge([
                'phone' => Auth::user()->phone,
                'contact_person' => Auth::user()->name,
            ]);
        }
        try {
            $this->merge([
                'phone' => trimPhone($this->phone),
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
        if (!$this->filled('customer_id') && $this->input('customer.type') !== 'unknown' && $this->input('type') === 'out') {
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
        $rules =array_merge($rules, [
            'contact_person' => 'required|string|max:255',
            'phone' => 'required|numeric|digits:' . RequestHelper::requestDomain()->options['phone_digits'],
            'email' => 'nullable|email',
            'status_tmp' => 'nullable|in:' . implode(',', ServiceCenter::$statuses),
            'address' => 'nullable|string',
            'address_type' => 'nullable|string',
            'creator_id' => 'nullable|exists:users,id',
            'name' => 'nullable|string|max:255',
            'type' => 'required|in:inner,out',
            'date_from' => 'required|date',
            'date_to' => 'required|date|after_or_equal:date_from',
            'description' => 'required|string|max:9999',
            'note' => 'nullable|string|max:99999',
            'machinery_id' => 'nullable|exists:machineries,id',
            'customer_id' => 'nullable|exists:dispatcher_customers,id',
            'workers.*' => 'nullable|exists:company_workers,id',
        ]);

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
