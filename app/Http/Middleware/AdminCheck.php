<?php

namespace App\Http\Middleware;

use App\User;
use Closure;
use Illuminate\Support\Facades\Auth;

class AdminCheck
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request $request
     * @param  \Closure                 $next
     * @param  string|null              $guard
     *
     * @return mixed
     */
    public function handle($request, Closure $next, $guard = null)
    {
        $auth = Auth::guard($guard);

        if (Auth::guard($guard)->guest()) {
            if ($request->ajax() || $request->wantsJson()) {
                return response('Unauthorized.', 401);
            }

            return redirect()->guest('/');
        }

        if (!$auth->user()->isSuperAdmin()) {
            return $request->ajax() || $request->wantsJson()
                ? response()->json([['Действие не доступно для текущей роли.'], 'modals' => 'Действие не доступно для текущей роли.'], 403)
                : response()->view('401')->setStatusCode(400);
        }

        return $next($request);
    }
}