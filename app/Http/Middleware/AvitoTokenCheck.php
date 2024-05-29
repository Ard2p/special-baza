<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AvitoTokenCheck
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        $tokens = [
//            '18uLCtsRnXpMlsEBUXlGBJOIJTuXDCVs7SSypTeePb3xyVnCsYsk1wahRRX5L0XrgmFsmYsIWaGiyt39Yv3duNuffgDhlsolgLe4UxoZO2y3KWZvFIDoCs3K2pSA2sJjdEcRdgZFyLVJAGaVyWUlOywOAHvv8teh',
            'Aq6CjJeVnfZ6ioGqvyUdKQVewoPHnRefFwGJdxvbB1Sv4OeIdlVT6b12Z4qc',
        ];
        if(!in_array($request->token, $tokens)) {
            return response('Unauthorized.', 401);
        }

        return $next($request);
    }
}
