<?php

namespace App\Proposal;

use Carbon\Carbon;
use App\Overrides\Model;

class ContractorTimestamps extends Model
{
    public $timestamps = false;

  /*  protected $dates = [
        'machinery_ready',
        'machinery_on_site',
        'end_of_work',
    ];*/

    protected $fillable = [
        'winner_steps', 'proposal_id', 'machinery_ready',
        'machinery_on_site', 'end_of_work',
    ];


    function getMachineryReadyAttribute($val)
    {
        return $val ? Carbon::parse($val)->format('d.m.Y H:i') : null;
    }

    function getMachineryOnSiteAttribute($val)
    {
        return $val ? Carbon::parse($val)->format('d.m.Y  H:i') : null;
    }

    function getEndOfWorkAttribute($val)
    {
        return $val ? Carbon::parse($val)->format('d.m.Y  H:i') : null;
    }
}
