<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Auth;

class Freeze
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
        if(Auth::check() && !Auth::user()->is_freeze){
            return $next($request);
        }

        return (($request->ajax())
            ? response()->json([['Действие не доступно. Аккаунт заморожен. ']], 401)
            : redirect('/new/user')->with('email_verify', ['Действие не доступно. Аккаунт заморожен. Чтобы "РАЗМОРОЗИТЬ АККАУНТ" - напишите в службу поддержи системы support@trans-baza.ru']));
    }
}
