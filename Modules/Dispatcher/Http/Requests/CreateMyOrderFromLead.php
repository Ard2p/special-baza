<?php

namespace Modules\Dispatcher\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\ContractorOffice\Entities\CompanyWorker;
use Modules\ContractorOffice\Entities\Vehicle\Price;

class CreateMyOrderFromLead extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
           // 'contractor_requisite.id' => 'required|integer',
           // 'contractor_requisite.type' => 'required|in:legal,individual',
            'cart' => 'required|array',
            'cart.*.type' => 'required|in:contractor,vehicle',
            'cart.*.cost_per_unit' => 'required|numeric|min:1|max:99999999',
            'cart.*.id' => 'required|integer',
            'cart.*.cashless_type' => 'nullable|in:' . implode(',', Price::getCashlessTypes()),
            'cart.*.value_added' => 'required|numeric|min:0|max:99999999',
            'cart.*.position_id' => 'required|exists:lead_positions,id',
            'cart.*.delivery_cost' => 'nullable|numeric|min:0|max:99999999',
            'cart.*.return_delivery' => 'nullable|numeric|min:0|max:99999999',
            'cart.*.comment' => 'nullable|string|max:500',
            'cart.*.order_type' => 'required|in:warm,cold',
            'cart.*.services' => 'nullable|array',
            'cart.*.services.*.name' => 'required|string|max:255',
            'cart.*.services.*.price' => 'required|numeric|min:0',
            'cart.*.company_worker_id' => [
                function ($attribute, $value, $fail) {

                    $id = (explode('.', $attribute))[1];
                    if($this->input("cart.{$id}.company_worker_id") && !CompanyWorker::query()->forBranch()->find($value)) {
                        $fail('Выберите водителя');
                    }
                },
            ],
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
