<?php

namespace Modules\Dispatcher\Http\Requests;

use App\Rules\Inn;
use Carbon\Carbon;
use Illuminate\Foundation\Http\FormRequest;

class IndividualRequisiteRequest extends FormRequest
{

    public function attributes()
    {
        return [
            'inn' => 'ИНН',
            'firstname' => 'Имя',
            'middlename' => 'Отчество',
            'surname' => 'Фамилия',
            'gender' => 'Пол',
            'birth_date' => 'Дата рождения',
            'passport_number' => 'Номер паспорта',
            'passport_date' => 'Дата выдачи паспорта',
            'issued_by' => 'Кем выдан',
            'register_address' => 'Адрес регистрации',
            'kp' => 'КП',
            'bank' => 'Наименование банка',
            'bik' => 'БИК',
            'ks' => 'Корреспондентский  счет',
            'rs' => 'Рассчетный счет',
          ];
    }

    protected function prepareForValidation()
    {
        if($this->filled('birth_date')) {
            $this->merge([
                'birth_date' => (string) Carbon::parse($this->input('birth_date'))
            ]);
        }

        if($this->filled('passport_date')) {
            $this->merge([
                'passport_date' => (string) Carbon::parse($this->input('passport_date'))
            ]);
        }
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'inn' => ['required', new Inn(), 'digits:12'],
            'firstname' => 'required|string|max:100',
            'middlename' => 'required|string|max:100',
            'surname' => 'required|string|max:100',
            'gender' => 'required|string|in:male,female',
            'birth_date' => 'nullable|date|before:' . now()->subYear(18),
            'passport_number' => 'nullable|string|max:100',
            'passport_date' => 'nullable|date',
            'issued_by' => 'nullable|string|max:255',
            'register_address' => 'required|string|max:255',
            'kp' => 'nullable|string|max:255',
            'bank' => 'required|string|max:255',
            'bik' => 'required|numeric',
            'ks' => 'required|numeric',
            'rs' => 'required|numeric',
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
