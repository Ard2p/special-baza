<?php

namespace Modules\Dispatcher\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateOffer extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'offer' => 'required|array',
            'offer.*.type' => 'required|in:contractor,vehicle',
            'offer.*.amount' => 'required|numeric|min:1',
            'offer.*.value_added' => 'nullable|numeric|min:0',
            'offer.*.id' => 'required|integer',
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
