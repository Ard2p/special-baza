<?php

namespace Modules\AdminOffice\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class Operator
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next, $guard = null)
    {
        $auth = Auth::guard($guard);

        if(!$auth->user()->hasRole('operator') && !$auth->user()->isSuperAdmin()) {

            return response()->json(['access_denied'], 403);
        }
        return $next($request);
    }
}
