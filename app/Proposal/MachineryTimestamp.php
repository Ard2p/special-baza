<?php

namespace App\Proposal;

use App\Machinery;
use Carbon\Carbon;
use App\Overrides\Model;

class MachineryTimestamp extends Model
{
    protected $fillable = [
        'machinery_id',
        'proposal_id',
        'step',
        'coordinates'
    ];


    function machine()
    {
        return $this->belongsTo(Machinery::class, 'machinery_id');
    }

    static function createTimestamp($machine_id, $proposal_id, $step, $coordinates = null)
    {
        return self::create([
            'machinery_id' => $machine_id,
            'proposal_id' => $proposal_id,
            'step' => $step,
            'coordinates' => $coordinates,
        ]);
    }


    static function updateGlobalTimestamp(Proposal $proposal, $step)
    {
        $proposal->load('machinery_timestamps');
        if ($proposal->machinery_timestamps->where('step', $step)->count() !== $proposal->machines()->count()) {
            return false;
        }
        $contractor_status = $proposal->contractor_timestamps;
        $state = false;
        switch ($step) {
            case 1:
                if ($contractor_status->winner_steps == 0) {
                    $contractor_status->machinery_ready = Carbon::now();
                    $contractor_status->winner_steps = 1;

                    $state = true;
                }
                break;
            case 2:
                if ($contractor_status->winner_steps == 1) {
                    $contractor_status->machinery_on_site = Carbon::now();
                    $contractor_status->winner_steps = 2;

                    $state = true;
                }
                break;
            case 3:
                if ($contractor_status->winner_steps == 2) {
                    $contractor_status->end_of_work = Carbon::now();
                    $contractor_status->winner_steps = 3;

                    $state = true;
                }
                break;
        }
        if ($state) {
            $contractor_status->save();
            return true;
        }

        return false;
    }

}
