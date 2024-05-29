<?php

namespace App\Http\Controllers\Avito\Dto\Casters;

use Spatie\DataTransferObject\Caster;

class AmountCaster implements Caster

{
    /**
     * @param  string|mixed  $value
     *
     * @return mixed
     */
    public function cast(mixed $value): bool
    {
        return intval($value) * 100;
    }
}
