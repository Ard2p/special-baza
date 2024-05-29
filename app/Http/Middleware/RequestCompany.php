<?php

namespace App\Http\Middleware;

use App\Service\RequestBranch;
use Closure;
use Illuminate\Support\Facades\Config;
use Modules\CompanyOffice\Entities\Company;

class RequestCompany
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        app()->make(RequestBranch::class);

        return $next($request);
    }
}
