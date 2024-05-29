<?php

namespace App\Http\Middleware;

use Closure;

class DisableEditMode
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
        inPlaceEditing(0);
        \App::setLocale('ru');
        return $next($request);
    }
}
