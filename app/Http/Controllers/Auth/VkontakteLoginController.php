<?php

namespace App\Http\Controllers\Auth;

use App\User\Auth\SocialVkontakteAccount;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Laravel\Socialite\Facades\Socialite;

class VkontakteLoginController extends Controller
{
    public function redirect()
    {
        return Socialite::with('vkontakte')->stateless()->redirect()->getTargetUrl();
    }

    /**
     * Return a callback method from facebook api.
     *
     * @return callback URL from facebook
     */
    public function callback(SocialVkontakteAccount $service)
    {
        $user = $service->createOrGetUser(Socialite::driver('vkontakte')->user());
        if(!$user){

            return redirect()->route('login')->withErrors([
                'no_email' => 'У данного аккаунта VKONTAKTE отсутствует Email адрес. Для корректной регистрации в системе необходимо иметь подтвержденный email адрес в социальной сети.'
            ]);
        }
        auth()->login($user);
        return redirect()->route('profile_index');
    }
}
