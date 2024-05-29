<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Auth;

class ContentAdmin
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

        if (!$auth->user() || !($auth->user()->isContentAdmin())) {
            return $request->ajax()
                ? response()->json([['Действие не доступно для текущей роли.'], 'modals' => 'Действие не доступно для текущей роли.'], 403)
                : response()->view('403')->setStatusCode(403);
        }

        return $next($request);
    }
}
