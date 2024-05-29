<?php

namespace App\Http\Middleware;

use App\User;
use Closure;
use Illuminate\Support\Facades\Auth;

class Performer
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
        if (Auth::check() && Auth::user()->isContractor()) {
            return $next($request);
        }


        return $request->ajax()
            ? response()->json([['Действие не доступно для текущей роли.'], 'modals' => ['Действие не доступно для текущей роли.']], 401)
            : response()->view('401')->setStatusCode(401);
    }
}