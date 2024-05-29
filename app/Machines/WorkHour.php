<?php

namespace App\Machines;

use Carbon\Carbon;
use App\Overrides\Model;
use Illuminate\Support\Facades\App;

class WorkHour extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'machine_id', 'from', 'to', 'day_name', 'is_free'
    ];

    protected $appends = ['rus_name'];

    protected $casts = ['is_free' => 'boolean'];
    static $day_type = [
        'Mon', 'Tue', 'Wed', 'Thu',
        'Fri', 'Sat', 'Sun',
    ];

    static function apiMap(WorkHour $hour)
    {
        return [
            'id' => $hour->id,
            'vehicle_id' => $hour->machine_id,
            'from' => $hour->from,
            'to' => $hour->to,
            'day_name' => $hour->day_name,
            'rus_name' => $hour->rus_name,
            'is_free' => $hour->is_free,
        ];
    }

    function getRusNameAttribute()
    {

        return Carbon::parse($this->day_name)->locale(App::getLocale())->dayName;
    }

    function getFromAttribute($val)
    {
        return (string)  Carbon::parse($val)->format('H:i');
    }

    function getToAttribute($val)
    {
        return (string) Carbon::parse($val)->format('H:i');
    }

    function getTimeFromAttribute()
    {
        return explode(':', $this->from);
    }

    function getTimeToAttribute()
    {
        return explode(':', $this->to);
    }
}
