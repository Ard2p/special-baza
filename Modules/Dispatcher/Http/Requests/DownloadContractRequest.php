<?php

namespace Modules\Dispatcher\Http\Requests;

use App\Helpers\RequestHelper;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DownloadContractRequest extends FormRequest
{
    protected function prepareForValidation()
    {
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {

        $rules = [];

        return array_merge($rules, [
            'with_stamp' => 'nullable|boolean',
            'documents_pack_id' => 'required|integer',
            'name' => 'required|string',
            'date' => 'nullable|date',
            'time' => 'nullable|string',
            'signatory_id' => 'nullable|integer'
        ]);
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
