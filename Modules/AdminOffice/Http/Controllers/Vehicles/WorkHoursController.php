<?php

namespace Modules\AdminOffice\Http\Controllers\Vehicles;

use App\Machinery;
use App\Machines\WorkHour;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class WorkHoursController extends Controller
{
    function mapHours($hours)
    {
        $hours = $hours->map(function ($item) {
            return WorkHour::apiMap($item);
        });
        return $hours;
    }

    function getWorkHours(Request $request, $id)
    {
        $vehicle = Machinery::forRegionalRepresentative()->findOrFail($id);

        return $this->mapHours($vehicle->work_hours);
    }

    function updateWorkHours(Request $request)
    {
        $rules = [
            '*.from' => 'required|date_format:H:i',
            '*.to' => 'required|date_format:H:i',
            '*.id' => 'required|exists:work_hours,id'
        ];
        if(!Auth::user()->isSuperAdmin()){
            $rules['*.vehicle_id'] = ['required',
                Rule::exists('machineries', 'id')->where('regional_representative_id', \Auth::id())
            ];
        }
        $errors = \Validator::make($request->all(), $rules)->errors()->getMessages();

        if($errors){
            return response()->json($errors, 400);
        }

        foreach ($request->all() as $item){
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
            ]);
        }
        return \response()->json();
    }
}
