<?php

namespace Modules\Dispatcher\Http\Requests;

use Carbon\Carbon;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SelectContrator extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [

            'contractor_id' => 'required|exists:company_branches,id',
            'vehicles' => 'required|array',
            'vehicles.*.id' => [
                'required',
                Rule::exists('machineries', 'id')->where('company_branch_id', $this->input('contractor_id'))
            ],
            "vehicles.*.order_cost" => "required|numeric|min:0|max:999999999",
            "vehicles.*.value_added" => "required|numeric|min:0|max:999999999",
            "vehicles.*.date_from" => 'required|date|after:' . now()->addDay()->format('Y-m-d'),
            "vehicles.*.order_type" => "required|in:shift,hour",
            "vehicles.*.order_duration" => "required|numeric|min:1|max:90",

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
