<?php

namespace Modules\ContractorOffice\Entities\Vehicle;

use Illuminate\Database\Eloquent\Model;

class WaypointsPrice extends Model
{

    protected $table = 'vehicle_waypoints_prices';

    public $timestamps = false;

    const TYPE_CASH = 'cash';
    const TYPE_CASHLESS_VAT = 'cashless_vat';
    const TYPE_CASHLESS_WITHOUT_VAT = 'cashless_without_vat';

    protected $fillable = [
        'distances',
    ];

    protected $casts = [
        'distances' => 'array'
    ];

    static function getTypes()
    {
        return [
            self::TYPE_CASHLESS_VAT,
            self::TYPE_CASH,
            self::TYPE_CASHLESS_WITHOUT_VAT
        ];
    }




}
