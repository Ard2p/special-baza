<?php

namespace App\Http\Middleware;

use App\User;
use Closure;
use Illuminate\Support\Facades\Auth;

class Customer
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
    public function handle($request, Closure $next)
    {
        if (Auth::check() && Auth::user()->hasRole('customer')) {
            return $next($request);
        }

        return $request->ajax()
            ? response()->json([['Действие не доступно для текущей роли.']], 401)
            : response()->view('401')->setStatusCode(401);

    }
}