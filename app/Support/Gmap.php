<?php

namespace App\Support;


use GoogleMaps\Facade\GoogleMapsFacade as GoogleMaps;

class Gmap
{
    private $key;

    function __construct($key = 'AIzaSyD6oW1AlDIxlNq4nXt7MAYT8l_8zqaCrro')
    {
        $this->key = $key;
    }

    function getGeometry($address)
    {

        $data = [
            'address' => $address,
            'key' => $this->key,
        ];
        $json = file_get_contents('https://maps.googleapis.com/maps/api/geocode/json?' . http_build_query($data));
        $array = json_decode($json, true);
        if ($array['status'] == 'OK') {
            $lat = $array['results'][0]['geometry']['location']['lat'];
            $lng = $array['results'][0]['geometry']['location']['lng'];
            return [
                'lat' => $lat,
                'lng' => $lng,
            ];
        }
        return false;
    }

    function getDistance($origin, array $destinations)
    {

        $data = [
            'units' => 'imperial',
            'origins' => $origin,
            'destinations' => trim(implode('|', $destinations), '|'),
            'key' => $this->key,
        ];
        $json = file_get_contents('https://maps.googleapis.com/maps/api/distancematrix/json?' . http_build_query($data));
        $array = json_decode($json, true);
        dd($array);
    }

    static function getCoordinatesByAddress($regionName, $city, $country = 'Россия')
    {
        $gmap = new self();
        $coordinates = $gmap->getGeometry("{$country} {$regionName} {$city}");
        if (!is_array($coordinates)) {
            return null;
        }


        return implode(',', $coordinates);
    }

    static function calculateRoute($origin, $destination)
    {

        $a = GoogleMaps::load('distancematrix')
            ->setParam([
                'origins' => $origin,
                'destinations' => $destination,
                'mode' => 'driving',
                'language' => 'GB'
            ])
            ->getResponseByKey('rows.elements');
        logger(json_encode($a));
        return isset($a['rows'][0]['elements'][0]['distance']['value']) ? $a['rows'][0]['elements'][0] : false;
    }
}
