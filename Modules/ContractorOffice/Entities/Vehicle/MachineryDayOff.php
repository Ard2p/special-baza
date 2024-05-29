<?php

namespace Modules\ContractorOffice\Entities\Vehicle;

use Illuminate\Database\Eloquent\Model;

class MachineryDayOff extends Model
{

    public $timestamps = false;
    protected $table = 'machinery_days_off';

    protected $fillable = [
        'date',
        'name',
    ];
}
