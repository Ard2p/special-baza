<?php

namespace App\Http\Middleware;

use Closure;

class TrimPhones
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
        $data = $request->all();
        $this->trimPhoneRequest($data);
        $request->merge($data);

        return $next($request);
    }


    function trimPhoneRequest(&$data)
    {
        foreach ($data as $key => &$item) {

            if($key === 'phone' && is_string($item)) {
                $data[$key] = trimPhone($item);
            }
            if($key === 'phones' && is_array($item)) {
                foreach ($item as $k => $phone) {
                    $item[$k] = trimPhone($phone);
                }
            }

            if(is_array($item)) {
                $this->trimPhoneRequest($item);
            }
        }

    }
}
