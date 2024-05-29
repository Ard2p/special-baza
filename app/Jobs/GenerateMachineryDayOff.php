<?php

namespace App\Jobs;

use App\Machinery;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\DB;
use Modules\CompanyOffice\Entities\Company\CompanyBranch;
use Modules\ContractorOffice\Entities\Vehicle\MachineryDayOff;

class GenerateMachineryDayOff implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;


    protected $machine, $branch;
    public $tries = 1;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(Machinery $machine, CompanyBranch $branch)
    {
        $this->machine = $machine;
        $this->branch = $branch;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $this->branch->load(['schedule', 'daysOff']);
        DB::beginTransaction();
        $this->machine->daysOff()->delete();
        foreach ($this->branch->daysOff as $item) {
            if (!$this->machine->daysOff()->whereDate('date', $item->date)->exists())
                $this->machine->daysOff()->save(new MachineryDayOff($item->toArray()));
        }

        $this->machine->generateDaysOff(true);


        foreach ($this->branch->schedule as $item) {
            $work = $this->machine->work_hours()->where('day_name', Carbon::now()->startOfWeek()->addDays($item->day_of_week)->format('D'))->firstOrFail();

            $work->update([
                'from' => ($item->day_off ? '08:00' : $item->min_hour),
                'to' => ($item->day_off ? '18:00' : $item->max_hour),
                'is_free' => toBool($item->day_off),
            ]);
        }
        DB::commit();
    }
}
