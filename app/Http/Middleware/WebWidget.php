<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Auth;

class WebWidget
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
        if (Auth::check() && Auth::user()->isWidget()) {
            $request->merge(['__webwidget' => '1']);
            return $next($request);
        }

        if(!Auth::check()){
            return redirect()->to(route('register_widget_front'));
        }

        return $request->ajax()
            ? response()->json([['Действие не доступно для текущей роли.']], 401)
            : response()->view('401')->setStatusCode(401);
    }
}
