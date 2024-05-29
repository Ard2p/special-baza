<?php

namespace App\Http\Controllers\Auth;

use App\Helpers\RequestHelper;
use App\User\Auth\SocialFacebookAccount;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Laravel\Socialite\Facades\Socialite;

class FacebookLoginController extends Controller
{
    /**
     * Create a redirect method to facebook api.
     *
     * @return void
     */
    public function redirect() 
    {
        $url =  Socialite::with('facebook')->stateless()->redirect()->getTargetUrl();

        return str_replace('trans-baza.ru/ru-ru', RequestHelper::requestDomain()->url, $url);
    }

    /**
     * Return a callback method from facebook api.
     *
     * @return callback URL from facebook
     */
    public function callback(SocialFacebookAccount $service)
    {
        $user = $service->createOrGetUser(Socialite::driver('facebook')->user());
        auth()->login($user);
        return redirect()->route('profile_index');
    }
}
