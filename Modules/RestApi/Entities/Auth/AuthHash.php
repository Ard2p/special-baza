<?php

namespace Modules\RestApi\Entities\Auth;

use App\User;
use App\Overrides\Model;
use Illuminate\Support\Str;

class AuthHash extends Model
{
    protected $fillable = [
        'user_id',
        'hash',
    ];

    function user()
    {
        return $this->belongsTo(User::class);
    }


    /**
     * Создать хеш для авторизации пользователя
     * @param $user_id
     * @return string
     */
    static function createHash($user_id)
    {
        $hash = Str::random(8);

        self::create([
            'user_id' => $user_id,
            'hash' => $hash,
        ]);

        return $hash;
    }

    /**
     * Получить Bearer по хешу из таблицы
     * @param $hash
     * @return array
     */
    static function getAuthDataByHash($hash)
    {
        $hash = self::query()->where('hash', $hash)->firstOrFail();

        $token = $hash->user->createToken('Token from Hash')->accessToken;

        self::query()->where('user_id', $hash->user->id)->delete();
        return [
            'token' => $token,
            'user' => \Modules\RestApi\Transformers\User::make($hash->user),
        ];
    }
}
