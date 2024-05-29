<?php

namespace App\Support;

use App\Overrides\Model;
use Modules\RestApi\Entities\Domain;

class Country extends Model
{
    protected $fillable = [
        'name',
        'alias',
        'domain_id',
    ];

    function regions()
    {
        return $this->hasMany(Region::class);
    }

    function phone_masks()
    {
        return $this->hasMany(PhoneCode::class);
    }

    function domain()
    {
        return $this->belongsTo(Domain::class);
    }

    function machine_masks()
    {
        return $this->hasMany(MachineCode::class);
    }

    static function Russia()
    {
        return self::whereAlias('russia')->firstOrFail();
    }

    function getVatAttribute($val)
    {
        return $val / 100;
    }

    function setVatAttribute($val)
    {
        $this->attributes['vat'] = round($val * 100);
    }
}
