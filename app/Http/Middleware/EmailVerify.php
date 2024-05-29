<?php

namespace App\Http\Middleware;

use App\User;
use Closure;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;

class EmailVerify
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



        $errors = [];
        if(!Auth::user()->native_region_id){
            $errors[] = 'Не выбран родной регион.';
        }
        if(!Auth::user()->native_city_id){
            $errors[] = 'Не выбран родной город.';
        }

        if (Auth::check() && Auth::user()->email_confirm && Auth::user()->phone_confirm && !$errors) {
            return $next($request);
        }
        if(!Auth::user()->email_confirm){
            $errors[] = 'Подтвердите email.';
        }

        if(!Auth::user()->phone_confirm){
            $errors[] = 'Подтвердите Телефон.';
        }

        Session::flash('email_verify', $errors);
        return redirect()->route('profile_index');
    }
}