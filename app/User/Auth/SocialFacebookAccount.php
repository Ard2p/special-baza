<?php

namespace App\User\Auth;

use App\User;
use App\Overrides\Model;
use Illuminate\Support\Facades\DB;
use Laravel\Socialite\Contracts\User as ProviderUser;

class SocialFacebookAccount extends Model
{

    protected $table = 'social_providers';

    protected $attributes = [
        'provider' => 'facebook'
    ];

    protected $fillable = ['user_id', 'provider_user_id', 'provider'];

    function user()
    {
        return $this->belongsTo(User::class);
    }

    public function createOrGetUser(ProviderUser $providerUser)
    {
        $account = self::whereProvider('facebook')
            ->whereProviderUserId($providerUser->getId())
            ->first();

        if ($account) {
            return $account->user;
        } else {
           DB::beginTransaction();

            $account = new SocialFacebookAccount([
                'provider_user_id' => $providerUser->getId(),
                'provider' => 'facebook'
            ]);

            $user = User::whereEmail($providerUser->getEmail())->first();

            if (!$user) {

                $user = User::register($providerUser->getEmail(), null);
            }

            $account->user()->associate($user);
            $account->save();

            DB::commit();
            return $user;

        }
    }
}
