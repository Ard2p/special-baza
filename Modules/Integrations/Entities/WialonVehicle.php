<?php

namespace Modules\Integrations\Entities;

use App\Machinery;
use App\Service\RequestBranch;
use GoogleMaps\Facade\GoogleMapsFacade as GoogleMaps;
use App\Overrides\Model;
use Modules\ContractorOffice\Entities\TelematicData;

class WialonVehicle extends Model
{
    protected $fillable = [
        'name',
        'wialon_id',
        'machinery_id',
        'coordinates',
        'last_position',
        'actual_data',
        'wialon_connection_id'
    ];

    protected $hidden = ['actual_data'];
    protected $appends = ['ignition'];

    protected $casts = [
      'actual_data' => 'object'
    ];

    function wialon_connection()
    {
        return $this->belongsTo(Wialon::class, 'wialon_connection_id');
    }

    function transbaza_vehicle()
    {
        return $this->belongsTo(Machinery::class, 'machinery_id');
    }

    function reports()
    {
        return $this->hasMany(TelematicData::class, 'telematic_vehicle_id')->where('telematic_type', '=', 'wialon');
    }

    function scopeForBranch($q, $branch_id = null)
    {
        $branch_id = $branch_id ?: app()->make(RequestBranch::class)->companyBranch->id;

        return $q->whereHas('wialon_connection', function ($q) use ($branch_id) {
            $q->forBranch($branch_id);
        });
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


    function getPosition()
    {
        $wialon_vehicle = $this->wialon_connection->searchItem($this->wialon_id);

        if (isset($wialon_vehicle['item'])) {
            $this->update(['actual_data' => $wialon_vehicle]);
        }

        if (isset($wialon_vehicle['item']['pos'])) {
            $coordinates = $wialon_vehicle['item']['pos'];

            $coordinates = [
                'lat' => $coordinates['y'],
                'lng' => $coordinates['x']
            ];

            return $coordinates;
        }

        return false;
    }

    function updateLastPosition()
    {
        $position = $this->getPosition();

        if ($position) {
            $coordinates = "{$position['lat']},{$position['lng']}";
            $geocode = GoogleMaps::load('geocoding')
                ->setParam(
                    [
                        'latlng' => $coordinates,
                        'language' => 'ru'
                    ])
                ->getResponseByKey('address_components');

            if (isset($geocode['results'][0]['address_components'])) {
                $components = $geocode['results'][0]['address_components'];
                foreach ($components as $component) {
                    $type = $component['types'][0];

                    switch ($type) {
                        case 'street_number' :
                            $street_number = "{$component['long_name']}, ";
                            break;
                        case 'route' :
                            $route = "{$component['long_name']} ";
                            break;
                        case 'locality' :
                            $locality = "{$component['long_name']}, ";
                            break;
                        case 'administrative_area_level_1' :
                            $region = "{$component['long_name']}";
                            break;
                    }
                }
                $formatted_address =  ($route ?? '') . ($street_number ?? '') . ($locality ?? '') . ($region ?? '');

                $this->update(
                    [
                        'coordinates' => $coordinates,
                        'last_position' =>$formatted_address
                    ]);
            }
        }
    }

    function getIgnitionAttribute()
    {
        $st =  $this->actual_data->item->prms->{'st1'}->v ?? null;
        if($st) {
            return $st;
        }
        return   $this->actual_data->item->prms->{'st2'}->v ?? null;
    }
}
