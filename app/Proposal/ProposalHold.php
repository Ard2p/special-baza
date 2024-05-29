<?php

namespace App\Proposal;

use App\City;
use App\Finance\TinkoffPayment;
use App\Machinery;
use App\Machines\FreeDay;
use App\Option;

use App\Service\OrderService;
use App\Support\Region;
use App\User;
use Carbon\Carbon;
use App\Overrides\Model;


class ProposalHold extends Model
{
    protected $fillable = [
        'amount', 'coordinates', 'date_from',
        'date_to', 'city_id', 'region_id', 'address'
    ];

    protected $dates = [
        'date_from',
        'date_to'
    ];

    function region()
    {
        return $this->belongsTo(Region::class);
    }

    function city()
    {
        return $this->belongsTo(City::class);
    }

    function tinkoff_payment()
    {
        return $this->hasOne(TinkoffPayment::class);
    }

    function holds()
    {
        return $this->hasMany(FreeDay::class);
    }


    function vehicles()
    {
        return $this->belongsToMany(Machinery::class, 'machine_holds')->withPivot('amount', 'date_from', 'date_to');
    }



    function setCoordinatesAttribute($val)
    {
        if ($val) {
            $coords = explode(',', trim($val));
            $query = "GeomFromText('POINT($coords[0] $coords[1])')";
            $this->attributes['coordinates'] = \DB::raw($query);
        }
    }

    function getCoordinatesAttribute($value)
    {
        return getDbCoordinates($value);
    }

}
