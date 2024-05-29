<?php

namespace App\Service;

use App\Support\Country;
use App\Support\Region;
use App\User;
use App\Widget\WidgetKeyHistory;
use App\Overrides\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Widget extends Model
{


    use SoftDeletes;

    protected $fillable = [
        'status', 'access_key', 'user_id', 'show_machines_list',
        'settings', 'region_id', 'city_id', 'type', 'name', 'country_id', 'locale'
    ];

    public const TYPES = [
        'all',
        'my',
        'has_system',
    ];

    public const SHOW_LIST = [
        'show',
        'disable',
    ];

    static public $time_type = [
            [
                'id' => 1,
                'name' => 'Час',
            ],
            [
                'id' => 2,
                'name' => 'Смена',
            ],
            [
                'id' => 3,
                'name' => 'День',
            ],
            [
                'id' => 4,
                'name' => 'Неделя',
            ],
            [
                'id' => 5,
                'name' => 'Месяц',
            ],
        ];

    private static $en_time_types = [
        1 => 'hour',
        2 => 'change',
        3 => 'day',
        4 => 'week',
        5 => 'month',
    ];

    static function getTimeType($key)
    {
         return self::$en_time_types[$key];
    }

    static function status($key)
    {
        return array_search($key, self::TYPES);
    }

    static function show_list($key)
    {
        return array_search($key, self::SHOW_LIST);
    }

    function user()
    {
        return $this->belongsTo(User::class);
    }

    function region()
    {
        return $this->belongsTo(Region::class);
    }

    function country()
    {
        return $this->belongsTo(Country::class);
    }

    function key_history()
    {
        return $this->hasMany(WidgetKeyHistory::class);
    }

    function widget_proposals()
    {
       return $this->hasMany(Proposal\WidgetProposal::class);
    }

    static function getSettings()
    {
        return [
            'color' => '#f4f4f4',
            'logo_url' => 'https://webwidget.trans-baza.ru/img/logos/logo-tb-eng-g-200.png',
            'width' => '350px',
            'height' => '1100px',
            'x_column' => '1',
            'y_column' => '10',
        ];
    }

    function getSettingsAttribute($val)
    {
        return $val ? json_decode($val, true) : self::getSettings();
    }
}
