<?php

namespace Modules\ContractorOffice\Entities\Telematics;

use App\Machinery;
use GoogleMaps\Facade\GoogleMapsFacade as GoogleMaps;
use GuzzleHttp\Client;
use GuzzleHttp\RequestOptions;
use Illuminate\Database\Eloquent\Model;

class Trekerserver extends Model
{

    private $apiUrl = 'http://trekerserver.ru/api/';
    protected $table = 'telematics_trekerserver';

    protected $fillable = [
        'name',
        'coordinates',
        'last_position',
    ];


    function vehicle()
    {
        return $this->morphOne(Machinery::class, 'telematics');
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

    /**
     * Получение текущей позиции техники по телематике
     * @return bool
     */
    function getPosition()
    {
        $client = new Client([
            'base_uri' => $this->apiUrl,
        ]);

        $request = $client->get('geolocation.php', [
            'http_errors' => false,
            RequestOptions::QUERY => [
                'vin' => $this->vehicle->vin
            ]
        ]);

        $response = json_decode($request->getBody()->getContents(), true);

        if (isset($response['status']) && $response['status']['code'] === 200) {
            return $response['geolocation'];
        }

        return false;
    }

    /**
     * Обновление последней позиции зафиксированной в телематике.
     * Получение адреса через api GOOGLE MAP
     */
    function updateLastPosition(): void
    {
        $position = $this->getPosition();
        if ($position) {
            $coordinates = "{$position['lat']},{$position['lon']}";
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
                $formatted_address = ($route ?? '') . ($street_number ?? '') . ($locality ?? '') . ($region ?? '');

                $this->update(
                    [
                        'coordinates' => $coordinates,
                        'last_position' => $formatted_address
                    ]);
            }
        }
    }


}
