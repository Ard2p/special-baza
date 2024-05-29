<?php

namespace Modules\RestApi\Http\Controllers;

use App\Helpers\RequestHelper;
use App\User\Auth\SocialFacebookAccount;
use App\User\Auth\SocialLinkedInAccount;
use App\User\Auth\SocialVkontakteAccount;
use Illuminate\Foundation\Application;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Laravel\Socialite\Facades\Socialite;

class SocialController extends Controller
{
   function vkAuth(SocialVkontakteAccount $service) {


       $user = $service->createOrGetUser(Socialite::driver('vkontakte')->stateless()->user());
       if(!$user){

           return response()->json([
               'no_email' => 'У данного аккаунта VKONTAKTE отсутствует Email адрес. Для корректной регистрации в системе необходимо иметь подтвержденный email адрес в социальной сети.'
           ], 419);
       }
       $token = $user->createToken('Token from Vk')->accessToken;

       return response()->json([
           'token' => $token,
           'user' => new \Modules\RestApi\Transformers\User($user),
       ]);
   }

   function  fbAuth(SocialFacebookAccount $service)
   {
       $redirect = config('services.facebook.redirect');
       config()->set('services.facebook.redirect', (RequestHelper::requestDomain()->alias !== 'ru' ? 'https://kinosk.com/social-auth/fb' : $redirect));

       $user = $service->createOrGetUser(Socialite::driver('facebook')->stateless()->user());

       if(!$user){

           return response()->json([
               'no_email' => 'У данного аккаунта FACEBOOK отсутствует Email адрес. Для корректной регистрации в системе необходимо иметь подтвержденный email адрес в социальной сети.'
           ], 419);
       }
       $token = $user->createToken('Token from FB')->accessToken;

       return response()->json([
           'token' => $token,
           'user' => new \Modules\RestApi\Transformers\User($user),
       ]);
   }

    function  LinkedAuth(SocialLinkedInAccount $service)
    {
        $user = $service->createOrGetUser(Socialite::driver('LinkedIn')->stateless()->user());

        if(!$user){

            return response()->json([
                'no_email' => 'У данного аккаунта LinkedIn отсутствует Email адрес. Для корректной регистрации в системе необходимо иметь подтвержденный email адрес в социальной сети.'
            ], 419);
        }
        $token = $user->createToken('Token from LN')->accessToken;

        return response()->json([
            'token' => $token,
            'user' => new \Modules\RestApi\Transformers\User($user),
        ]);
    }
}
