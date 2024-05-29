<?php

namespace App\User\Auth;

use App\User;
use App\Overrides\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Laravel\Socialite\Contracts\User as ProviderUser;

class SocialVkontakteAccount extends Model
{


    protected $table = 'social_providers';

    protected $attributes = [
        'provider' => 'vkontakte'
    ];

    protected $fillable = ['user_id', 'provider_user_id', 'provider'];

    function user()
    {
        return $this->belongsTo(User::class);
    }

    public function createOrGetUser(ProviderUser $providerUser)
    {
        //dd($providerUser);



        $account = self::whereProvider('vkontakte')
            ->whereProviderUserId($providerUser->getId())
            ->first();

        if ($account) {

            return $account->user;
        } else {
            DB::beginTransaction();

            if(!isset($providerUser->accessTokenResponseBody['email'])){
                DB::rollBack();
                return false;
            }
             $email = $providerUser->accessTokenResponseBody['email'];

            $account = new self([
                'provider_user_id' => $providerUser->getId(),
                'provider' => 'vkontakte'
            ]);

            $user = User::whereEmail($email)->first();

            if (!$user) {

                $user = User::register($email, null);
            }

            $account->user()->associate($user);
            $account->save();

            DB::commit();
            return $user;

        }
    }
}
