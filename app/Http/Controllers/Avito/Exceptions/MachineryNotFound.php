<?php

namespace App\Http\Controllers\Avito\Exceptions;

use Exception;

class MachineryNotFound extends Exception
{
    public function __construct($message, $data = [])
    {
        parent::__construct($message);
    }
}
