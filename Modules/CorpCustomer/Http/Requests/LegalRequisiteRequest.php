<?php

namespace Modules\CorpCustomer\Http\Requests;

use App\Helpers\RequestHelper;
use App\Rules\Inn;
use App\Rules\Kpp;
use App\Rules\Ogrn;
use Illuminate\Foundation\Http\FormRequest;

class LegalRequisiteRequest extends FormRequest
{

    protected function prepareForValidation()
    {

        $this->merge([
            'phone' => trimPhone($this->phone)
        ]);
    }

    public function attributes()
    {
        return [
            'full_name' => 'Наименование',
            'short_name' => 'Сокращенное наименование',
            'address' => 'Адрес',
            'zip_code' => 'Почтовый индекс',
            'email' => 'Email',
            'phone' => 'Телефон',
            'inn' => 'ИНН',
            'kpp' => 'КПП',
            'ogrn' => 'ОГРН',
        ];
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        $id = $this->route()->parameter('brand');

        return [
            'full_name' => 'required|string|min:2|max:255',
            'short_name' => 'required|string|min:2|max:255',
            'address' => 'required|string|min:5|max:1000',
            'zip_code' => 'required|string|min:2|max:255',
            'email' => 'required|email|max:255',
            'phone' => 'required|numeric|digits:' .  RequestHelper::requestDomain()->options['phone_digits'],
            'inn' => [new Inn, 'unique:corp_brands,inn' . ($id ? ",{$id}" : '')],
            'kpp' => ['nullable', new Kpp],
            'ogrn' => new Ogrn,
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
