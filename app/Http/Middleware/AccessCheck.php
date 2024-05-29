<?php

namespace App\Http\Middleware;

use App\User;
use Closure;
use Illuminate\Support\Facades\Auth;

class AccessCheck
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request $request
     * @param  \Closure $next
     * @param  string|null $guard
     *
     * @return mixed
     */
    public function handle($request, Closure $next, $block, $method = null)
    {

        $access = (Auth::guard('api')->user()->hasAccessTo($block, $method));

        return $access
            ? $next($request)
            : response()->json(['message' => 'Нет доступа к блоку.'], 403);
    }
}