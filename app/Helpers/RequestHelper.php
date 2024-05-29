<?php

namespace App\Helpers;

use App\Service\RequestBranch;

class RequestHelper
{

    static function requestDomain($key = null){
        return  app()->make(RequestBranch::class)->getDomain($key);
    }
}