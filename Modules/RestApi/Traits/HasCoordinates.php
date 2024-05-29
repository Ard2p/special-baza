<?php

namespace Modules\RestApi\Traits;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Modules\Dispatcher\Entities\DispatcherOrder;
use Modules\Orders\Entities\Order;
use Modules\Orders\Entities\OrderDocument;

trait HasCoordinates
{

    /**
     * @param $val
     */
    function setCoordinatesAttribute($val)
    {
        if ($val) {
            $coords = explode(',', trim($val));
            $query = "GeomFromText('POINT($coords[0] $coords[1])')";
            $this->attributes['coordinates'] = \DB::raw($query);
        }
    }

    /**
     * @param $value
     * @return string|null
     */
    function getCoordinatesAttribute($value)
    {
        return getDbCoordinates($value);
    }

}
