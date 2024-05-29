<?php

namespace Modules\CompanyOffice\Http\Controllers;

use App\Jobs\GenerateMachineryDayOff;
use App\Machinery;
use App\Machines\WorkHour;
use App\Service\RequestBranch;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\CompanyOffice\Entities\Company\CompanyBranch;
use Modules\CompanyOffice\Entities\Company\CompanyDayOff;
use Modules\CompanyOffice\Entities\Company\CompanySchedule;
use Modules\CompanyOffice\Entities\Company\CompanyWorkHours;
use Modules\CompanyOffice\Services\CompanyRoles;
use Modules\ContractorOffice\Entities\Vehicle\MachineryDayOff;

class ScheduleController extends Controller
{
    /** @var CompanyBranch */
    private $currentBranch;

    public function __construct(Request $request, RequestBranch $companyBranch)
    {
        $this->currentBranch = $companyBranch->companyBranch;


        $block = $this->currentBranch->getBlockName(CompanyRoles::BRANCH_CALENDAR);
        $this->middleware("accessCheck:{$block}," . CompanyRoles::ACTION_SHOW)->only(
            [
                'index',
                'store',
                'update',
                'destroy',
            ]);
    }

    /**
     * Display a listing of the resource.
     * @return Response
     */
    public function index()
    {
        $this->currentBranch->schedule->load('workHours');
        return response()->json([
            'schedule' => $this->currentBranch->schedule,
            'days_off' => $this->currentBranch->daysOff
        ]);
    }


    function dayInfo(Request $request)
    {
        $dayNum = $request->input('day') == 0 ? 6 : $request->input('day') - 1;
        $sch = $this->currentBranch->schedule()->where('day_of_week', $dayNum)->firstOrFail();

        return response()->json([
            'start_time' => $sch->min_hour
        ]);
    }


    /**
     * Update the specified resource in storage.
     * @param Request $request
     * @param int $id
     * @return Response
     * @throws \Exception
     */
    public function store(Request $request, $id)
    {
        $request->validate([
            'schedule.*.day_of_week' => 'required|min:0|max:6|distinct',
            'schedule.*.day_off' => 'nullable|boolean',
            'schedule.*.work_hours.*.time_from' => 'required|date_format:H:i',
            'schedule.*.work_hours.*.time_to' => 'required|date_format:H:i|after:schedule.*.work_hours.*.time_from',

            'days_off.*.date' => 'required|date',
            'days_off.*.name' => 'required|string|max:255',
            'refresh_type' => 'nullable|in:all,exclude,include',
            'machinery' => 'nullable|array'
        ]);

        DB::beginTransaction();

        $refresh = toBool($request->input('refresh'));

        foreach ($request->input('schedule') as $item) {
            /** @var CompanySchedule $sch */
            $sch = $this->currentBranch->schedule()->where('day_of_week', $item['day_of_week'])->firstOrFail();
            $dayOff = toBool($item['day_off']) || empty($item['work_hours']);
            $sch->update([
                'day_off' => $dayOff
            ]);

            if ($dayOff) {
                $sch->workHours()->delete();

            } else {
                $ids = [];
                foreach ($item['work_hours'] as $time) {

                    $fields = [
                        'time_from' => $time['time_from'],
                        'time_to' => $time['time_to'],
                    ];
                    if (!empty($time['id'])) {
                        $timeItem = $sch->workHours()->findOrFail($time['id']);
                        $timeItem->update($fields);
                    } else {
                        $timeItem = new CompanyWorkHours($fields);
                        $sch->workHours()->save($timeItem);
                    }

                    $ids[] = $timeItem->id;
                }
                $sch->workHours()->whereNotIn('id', $ids)->delete();
            }
        }
        $dayOffIds =[ ];
        foreach ($request->input('days_off') as $dayOff) {
             $fields = [
                 'name' => $dayOff['name'],
                 'date' => $dayOff['date'],
             ];
             if(!empty($dayOff['id'])) {
                 $this->currentBranch->daysOff()->where('id', $dayOff['id'])->update($fields);
                 $dayOffIds[] = $dayOff['id'];

             }else {
                 $df = $this->currentBranch->daysOff()->save(new CompanyDayOff($fields));
                 $dayOffIds[] = $df->id;
             }
        }
        $this->currentBranch->daysOff()->whereNotIn('id', $dayOffIds)->delete();
        DB::commit();



        if($refresh) {

            $machinery = $this->currentBranch->machines();
            switch ($request->input('refresh_type')) {
                case 'include':
                    $machinery->whereIn('id', $request->input('machinery'));
                    break;
                case 'exclude':
                    $machinery->whereNotIn('id', $request->input('machinery'));
                    break;
            }
            /** @var Machinery $machine */
            foreach ($machinery->get() as $machine) {
                dispatch(new GenerateMachineryDayOff($machine, $this->currentBranch));
            }
        }



        return response()->json();
    }

    /**
     * Remove the specified resource from storage.
     * @param int $id
     * @return Response
     */
    public function destroy($id)
    {
        //
    }
}
