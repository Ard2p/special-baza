<?php

namespace Modules\RestApi\Http\Middleware;

use App\Seo\SeoBlock;
use Closure;
use Illuminate\Http\Request;

class AppendSeo
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {

        $response =  $next($request);

        if($response->headers->get('content-type') == 'application/json')
        {
            $seo = SeoBlock::renderBottom(true);
            $collection = collect($response->original);

            $collection->put('seo_text', $seo);
            $response->setContent($collection->toJson());

           // dd($response);
        }

        return $response;
    }
}
