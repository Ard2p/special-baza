<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Auth;

class RegionalRepresentative
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next, $guard = null)
    {
        $auth = Auth::guard($guard);

        if (Auth::guard($guard)->guest()) {
            if ($request->ajax() || $request->wantsJson()) {
                return response('Unauthorized.', 401);
            } else {
                return redirect()->guest('/');
            }
        }

        if(!$auth->check()){
            return $request->wantsJson() || $request->ajax()
                ? response()->json([['Действие не доступно для текущей роли.'], 'modals' => 'Действие не доступно для текущей роли.'], 401)
                : response()->view('401')->setStatusCode(401);
        }

        if (!(Auth::user()->isSuperAdmin() || Auth::user()->isRegionalRepresentative())) {
            return   response()->json([['Действие не доступно для текущей роли.'], 'modals' => 'Действие не доступно для текущей роли.'], 400);
        }

        return $next($request);
    }
}
