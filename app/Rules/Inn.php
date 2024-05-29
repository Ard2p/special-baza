<?php

namespace App\Rules;

use App\Service\RequestBranch;
use Illuminate\Contracts\Validation\Rule;

class Inn implements Rule
{
    /**
     * Create a new rule instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Determine if the validation rule passes.
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @return bool
     */
    public function passes($attribute, $value)
    {
        $isNotRf = app(RequestBranch::class)?->companyBranch?->is_not_rf;
        return $isNotRf || is_valid_inn($value);
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return 'Некорректный ИНН';
    }
}
