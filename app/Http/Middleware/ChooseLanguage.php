<?php

namespace App\Http\Middleware;

use App\Option;
use Closure;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Cookie;
use Modules\RestApi\Entities\Domain;

class ChooseLanguage
{
    /**
     * Handle an incoming request.
     *
     * @param \Illuminate\Http\Request $request
     * @param \Closure $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
       // Config::set('request_domain', Domain::whereAlias(request()->header('domain', 'ru'))->first());

        $locale = \App::getLocale();

         $allowed_locales = config('app.locales');


         if($request->header('Language')){
             $locale = $request->header('Language');
         }
         if($locale){

             if(in_array($locale, $allowed_locales)) {

                 \App::setLocale($locale);

                 if ($request->header('domain') !== 'ru') {
                      Config::set('in_mode', true);
                 }

             }
         }else{
             \App::setLocale(config('app.locale'));

         }

        return $next($request);
    }
}
