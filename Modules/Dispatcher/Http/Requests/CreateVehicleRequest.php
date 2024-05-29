<?php

namespace Modules\Dispatcher\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class CreateVehicleRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
           'name' => 'nullable|string|max:255',
           'type_id' => 'required|exists:types,id',
           'brand_id' => 'nullable|exists:brands,id',
           'comment' => 'nullable|string|max:255',
           'contractor_id' => [
               Rule::exists('dispatcher_contractors', 'id')->where('user_id', Auth::id())
           ]
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
