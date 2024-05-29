<?php

namespace Modules\Dispatcher\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AcceptDispatcherOffer extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'offer_id' => 'required',
            'positions' => 'nullable|array',
            'positions.*.position_id' => "nullable|exists:lead_offer_positions,lead_offer_id,{$this->input('offer_id')}",
            'positions.*.value_added' => 'nullable|numeric|min:0|max:999999999',
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
