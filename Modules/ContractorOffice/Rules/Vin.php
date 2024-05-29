<?php

namespace Modules\ContractorOffice\Rules;

use Illuminate\Contracts\Validation\Rule;

class Vin implements Rule
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
        $vin = strtolower($value);
        if (!preg_match('/[A-HJ-NPR-Z0-9]{17}/i', $vin)) {

            return false;
        }

        return true;
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return 'Wrong vin';
    }
}
