<?php

namespace App;

use App\Overrides\Model;

class Option extends Model
{
    public $primaryKey = 'key';

    protected $fillable = ['key', 'value'];

    public $incrementing = false;


    static $systemLocales = [
        'en',
        'th',
        'it',
    ];

    static function get($key)
    {
        $item = self::find($key);
        return $item ? $item->value : null;
    }

    static function set($key, $value)
    {
        $item = self::find($key);
        return $item
            ? $item->update([
            'value' => $value
             ])
            : self::create([
                'key' => $key,
                'value' => $value
            ]);
    }

    static function getLocales()
    {
        $arr = self::$systemLocales;
        $arr[] = 'ru';
        return $arr;
    }
}
