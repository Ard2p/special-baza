<?php

namespace App\Traits;

use Illuminate\Support\Facades\Redis;

trait Cacheables
{

    static $cacheKey;

    protected static function bootCacheables()
    {
        static::$cacheKey = strtolower(class_basename(static::class));

        static::created(function (self $model) {
            static::deleteCache();
        });

        static::updated(function (self $model) {
            static::deleteCache();
        });

        static::deleted(function (self $model) {
            static::deleteCache();
        });
    }

    static function deleteCache()
    {
        $redisPrefix = config('database.redis.options.prefix') . config('cache.prefix');

        $keys = Redis::keys($redisPrefix. ':' . static::$cacheKey . ':*');

        if ($keys)
            Redis::del($keys);
    }

    public $skipCacheFields = [
        'perpage', 'sortby', 'page', 'sortdir', 'sortdesc'
    ];

    function getCacheKey()
    {
        return static::$cacheKey;
    }

}
