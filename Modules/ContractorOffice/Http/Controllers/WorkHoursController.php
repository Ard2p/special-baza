<?php

namespace Modules\ContractorOffice\Http\Controllers;

use App\Machinery;
use App\Machines\WorkHour;
use App\Service\RequestBranch;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Modules\CompanyOffice\Entities\Company\CompanyDayOff;
use Modules\CompanyOffice\Services\CompanyRoles;

class WorkHoursController extends Controller
{
    private $companyBranch;

    public function __construct(Request $request, RequestBranch $companyBranch)
    {
        $this->companyBranch = $companyBranch->companyBranch;

        $block = $this->companyBranch->getBlockName(CompanyRoles::BRANCH_CALENDAR);

        $this->middleware("accessCheck:{$block}," . CompanyRoles::ACTION_SHOW)->only('getWorkHours');
        $this->middleware("accessCheck:{$block}," . CompanyRoles::ACTION_CREATE)->only(['updateWorkHours']);


    }

    function mapHours($hours)
    {
        $hours = $hours->map(function ($item) {
            return WorkHour::apiMap($item);
        });
        return $hours;
    }

    function getWorkHours(Request $request, $id)
    {
        /** @var Machinery $vehicle */
        $vehicle = Machinery::forBranch()->findOrFail($id);

        return  response()->json([
            'schedule' =>  $this->mapHours($vehicle->work_hours),
            'days_off' => $vehicle->daysOff,
        ]);
    }


    function updateWorkHours(Request $request)
    {
        $request->validate( [
            'hours*.from' => 'required|date_format:H:i',
            'hours*.to' => 'required|date_format:H:i|after:hours.*.from',
            'hours*.vehicle_id' => ['required',
                Rule::exists('machineries', 'id')->where('company_branch_id', $this->companyBranch->id)
            ],
            'hours*.id' => 'required|exists:work_hours,id',
            'days_off.*.date' => 'required|date',
            'days_off.*.name' => 'required|string|max:255',
        ]);

        $data = $request->input('hours');

        DB::beginTransaction();

        /** @var Machinery $machinery */
        $machinery = Machinery::query()->forBranch()->findOrFail($data[0]['vehicle_id']);
        foreach ($data as $item){
            $from = Carbon::createFromFormat('H:i', $item['from']);
            $to = Carbon::createFromFormat('H:i', $item['to']);
            if($from->gt($to)){
                $tmp = clone $from;
                $from  = clone $to;
                $to = $tmp;
            }
            $work = WorkHour::where('machine_id', $item['vehicle_id'])->findOrFail($item['id']);

            $work->update([
                'from' => $from->format('H:i'),
                'to' => $to->format('H:i'),
                'is_free' => toBool($item['is_free'] ?? false),
            ]);
        }

        $dayOffIds =[ ];
        foreach ($request->input('days_off') as $dayOff) {
            $fields = [
                'name' => $dayOff['name'],
                'date' => $dayOff['date'],
            ];
            if(!empty($dayOff['id'])) {
                $machinery->daysOff()->where('id', $dayOff['id'])->update($fields);
                $dayOffIds[] = $dayOff['id'];

            }else {
                $df = $machinery->daysOff()->save(new CompanyDayOff($fields));
                $dayOffIds[] = $df->id;
            }
        }
        $machinery->daysOff()->whereNotIn('id', $dayOffIds)->delete();

        if(toBool($request->input('refresh'))) {

            $machinery->generateDaysOff();
        }

        DB::commit();
        return \response()->json();
    }
}
