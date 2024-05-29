<?php

namespace App\User\Auth;

use App\User;
use App\Overrides\Model;
use Illuminate\Support\Facades\DB;
use Laravel\Socialite\Contracts\User as ProviderUser;

class SocialLinkedInAccount extends Model
{

    protected $table = 'social_providers';

    protected $attributes = [
        'provider' => 'linkedin'
    ];

    protected $fillable = ['user_id', 'provider_user_id', 'provider'];

    function user()
    {
        return $this->belongsTo(User::class);
    }

    public function createOrGetUser(ProviderUser $providerUser)
    {

        $account = self::whereProvider('linkedin')
            ->whereProviderUserId($providerUser->getId())
            ->first();

        if ($account) {

            return $account->user;
        } else {
            DB::beginTransaction();


            if(!$providerUser->getEmail()){
                DB::rollBack();
                return false;
            }
            $email = $providerUser->getEmail();

            $account = new self([
                'provider_user_id' => $providerUser->getId(),
                'provider' => 'linkedin'
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
