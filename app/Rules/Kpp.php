<?php

namespace App\Rules;

use App\Service\RequestBranch;
use Illuminate\Contracts\Validation\Rule;

class Kpp implements Rule
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

        if($isNotRf) {
            return  true;
        }
        $result = false;
        $kpp = (string) $value;
        if (!$kpp) {
        return false;
        } elseif (strlen($kpp) !== 9) {
            return false;
        } elseif (!preg_match('/^[0-9]{4}[0-9A-Z]{2}[0-9]{3}$/', $kpp)) {
            return false;
        } else {
            $result = true;
        }
        return $result;
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return 'Некорректный КПП';
    }
}
