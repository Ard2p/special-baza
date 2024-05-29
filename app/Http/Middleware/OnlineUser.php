<?php

namespace App\Http\Middleware;

use Carbon\Carbon;
use Closure;
use Illuminate\Support\Facades\Auth;
//use Vsch\TranslationManager\Translator;

class OnlineUser
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request $request
     * @param  \Closure                 $next
     * @return mixed
     */
    public function handle($request, Closure $next, $guard = null)
    {
//        $auth = Auth::guard('api');
//
//        if ($auth->check() && Carbon::now()->subMinutes(3)->gt($auth->user()->last_activity )) {
//
//          //  $auth->user()->update([
//          //      'last_activity' => Carbon::now()
//          //  ]);
//        }
        return $next($request);
    }
}
