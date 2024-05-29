<?php

namespace Modules\FMSAPI\Entities;


use App\City;
use App\Machinery;
use App\Machines\FreeDay;
use App\Support\Gmap;
use App\Support\Region;
use App\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;
use Modules\Integrations\Entities\Integration;

class FmsApi
{

    public $fails = [];
    private $integration_id;

    public function __construct()
    {
        \Log::info('go!');
        $this->integration_id = Integration::whereName('FMS')->first()->id;
    }

    static function isCurrentRequest()
    {
        return Auth::check() ? Auth::user()->email === 'fms@c-cars.tech' : false;
    }



/*
    static function getValidatorRules()
    {
        return [
            'action' => 'required|array',
            'action.*.table' => 'required|in:users,vehicles,calendars',
            'action.*.action' => 'required|in:add,change,delete',
            'action.*.data.*' => 'required|integer',
            'action.*.data' => 'required|array',
        ];
    }


    function processData($data)
    {

        foreach ($data['action'] as $item) {
            $this->processItem($item);
        }

        return ['fails' => $this->fails];
    }


    function runMethod($model, $action, $params)
    {

        switch ($action) {
            case 'add' :
            case 'change':
            case 'delete':
                $action = $action . $model;
                $this->$action($params);
                break;
            default:
                throw new InvalidArgumentException('Unknown method ' . $action);
                break;
        }


    }

    private function processItem($item)
    {

        foreach ($item['data'] as $current_id) {
            \Log::info('Process ' . $item['table'] . ' ' . $item['action'] . ' ' . $current_id);

            switch ($item['table']) {
                case 'users':
                    $this->runMethod('User', $item['action'], $current_id);
                    break;
                case 'vehicles':
                    $this->runMethod('Machinery', $item['action'], $current_id);
                    break;
                case 'calendars':
                    $this->runMethod('Calendar', $item['action'], $current_id);
                    break;
            }
        }

    }


    private function addUser($id)
    {
        $user = DB::connection('pgsql')->table('users')->find($id);

        try {
            $phone = User::trimPhone($user->phone);

            DB::beginTransaction();
            $new_user = User::register($user->email, $phone, 'contractor');
            $new_user->integrations()->attach([$this->integration_id => ['native_id' => $user->user_id]]);
            DB::commit();

        } catch (\Exception $exception) {
            $this->setFail($id, 'users', 'Cannot add new user.');
        }

        return $this;
    }

    private function changeUser($id)
    {

        try {
            $user = DB::connection('pgsql')->table('users')->find($id);
            $transbaza_user = User::whereHas('integrations', function ($q) use ($user) {
                return $q->whereName('FMS')->where('native_id', $user->user_id);
            })->first();
            $phone = User::trimPhone($user->phone);
            DB::beginTransaction();
            $transbaza_user->update([
                'email' => $user->email,
                'phone' => $phone
            ]);
            DB::commit();

        } catch (\Exception $exception) {
            $this->setFail($id, 'users', 'Cannot update user');
        }

        return $this;
    }

    private function addCalendar($id)
    {
        try {
            DB::beginTransaction();
            $calendar_fms = DB::connection('pgsql')->table('calendars')->find($id);

            $start_date = Carbon::createFromFormat('Y-m-d', $calendar_fms->date_from)->startOfDay();
            $end_date = Carbon::createFromFormat('Y-m-d', $calendar_fms->date_to)->startOfDay();

            if ($start_date > $end_date) {
                $c = $end_date;
                $end_date = $start_date;
                $start_date = $c;
            }

            $transbaza_machine = Machinery::where('integration_native_id', $calendar_fms->car_id)->checkAvailable($start_date, $end_date)->first();

            if(!$transbaza_machine) {
                $this->setFail($id, 'vehicles', 'Cannot update calendar for this period.');
                goto end;

            }
            $calendar_tb = FreeDay::create([
                'startDate' => $start_date,
                'endDate' => $end_date,
                'type' => 'busy',
                'machine_id' => $transbaza_machine->id
            ]);
            DB::connection('pgsql')->table('calendars')->where('id', $id)->update(['transbaza_id' => $calendar_tb->id]);

            DB::commit();
        } catch (\Exception $exception) {
            \Log::error($exception->getMessage(), $exception->getTrace());
            $this->setFail($id, 'vehicles', 'Cannot update calendar for this period.');
        }
        end:
       return $this;
    }

    function changeCalendar($id)
    {
        try {
            DB::beginTransaction();
            $calendar_fms = DB::connection('pgsql')->table('calendars')->find($id);

            $start_date = Carbon::createFromFormat('Y-m-d', $calendar_fms->date_from)->startOfDay();
            $end_date = Carbon::createFromFormat('Y-m-d', $calendar_fms->date_to)->startOfDay();

            if ($start_date->gt($end_date) ) {
                $c = $end_date;
                $end_date = $start_date;
                $start_date = $c;
            }

            $transbaza_machine = Machinery::where('integration_native_id', $calendar_fms->car_id)->checkAvailable($start_date, $end_date, true)->first();

            $calendar_tb = FreeDay::whereType('busy')->where('machine_id', $transbaza_machine->id)->find($calendar_fms->transbaza_id);
            Log::info( (string) $start_date . ' ' . (string) $end_date);
            $calendar_tb->update([
                'startDate' => $start_date,
                'endDate' => $end_date,
       
       
            ]);

            DB::commit();
        } catch (\Exception $exception) {
            \Log::error($exception->getMessage(), $exception->getTrace());
            $this->setFail($id, 'calendars', 'Cannot update calendar for this period.');
        }

    }

    function deleteCalendar($id)
    {
        try {
        $calendar = DB::connection('pgsql')->table('calendars')->find($id);
        DB::connection('pgsql')->table('calendars')->where('id', $id)->delete();
        $transbaza_calendar = FreeDay::whereType('busy')->find($calendar->transbaza_id);

        if($transbaza_calendar){
            $transbaza_calendar->delete();
        }


        } catch (\Exception $exception) {
            \Log::error($exception->getMessage(), $exception->getTrace());
            $this->setFail($id, 'calendars', 'Cannot delete calendar');
        }
       return $this;
    }

    private function addMachinery($id)
    {
        try {
            DB::beginTransaction();
            $machinery = DB::connection('pgsql')->table('vehicles')->select(DB::raw('*, array_to_json(image) as image'))->find($id);

            $user = DB::connection('pgsql')->table('users')->find($machinery->user_id);

            $transbaza_user = User::whereHas('integrations', function ($q) use ($user) {
                return $q->whereName('FMS')->where('native_id', $user->user_id);
            })->first();

            $number = trim($machinery->licencePlate);

            $check = Machinery::whereNumber($number)->orWhere('integration_native_id', $machinery->car_id)->first();
            if ($check) {
                $this->setFail($id, 'vehicles', 'Already exist');
                goto end;
            }
            if (!$transbaza_user) {
                $this->setFail($id, 'vehicles', 'user not found');
                goto end;
            }
            $city = City::with('region')->find($machinery->city_id);
            $machine_data = [
                'machine_type' => 'machine',
                'address' => $machinery->address,
                'integration_native_id' => $machinery->car_id,
                'region_id' => $machinery->region_id,
                'city_id' => $machinery->city_id,
                'type' => $machinery->category_id,
                'brand_id' => $machinery->brand_id ?: 0,
                'sum_hour' => round(str_replace(',', '.', $machinery->rent_price) * 100),
                'sum_day' => round(str_replace(',', '.', $machinery->shift_rent_price) * 100),
                'change_hour' => $machinery->shift_duration,
                'name' => $machinery->name,
                'number' => $number,
                'scans' => json_encode([]),
                'user_id' => $transbaza_user->id,
                'coordinates' => $this->getCoordinates($city->region->name, $city->name),
                'photo' => null

            ];
            $images = json_decode($machinery->image);
            $machine = Machinery::create($machine_data);
            $machine->putPhotosFromBase64($images);
            $machine->generateChpu(true);
            DB::commit();
        } catch (\Exception $exception) {
            \Log::error($exception->getMessage(), $exception->getTrace());
            $this->setFail($id, 'vehicles', 'Incorrect request');
        }

        end:
        return $this;
    }

    private function changeMachinery($id)
    {
        try {
            DB::beginTransaction();
            $machinery = DB::connection('pgsql')->table('vehicles')->select(DB::raw('*, array_to_json(image) as image'))->find($id);

            $transbaza_machine = Machinery::where('integration_native_id', $machinery->car_id)->first();
            $number = trim($machinery->licencePlate);
            if (!$transbaza_machine) {
                $this->setFail($id, 'vehicles', 'vehicle not found');
                goto end2;
            }
            $city = City::with('region')->find($machinery->city_id);
            $machine_data = [
                'machine_type' => 'machine',
                'address' => $machinery->address,
                'region_id' => $machinery->region_id,
                'city_id' => $machinery->city_id,
                'type' => $machinery->category_id,
                'brand_id' => $machinery->brand_id ?: 0,
                'sum_hour' => round(str_replace(',', '.', $machinery->rent_price) * 100),
                'sum_day' => round(str_replace(',', '.', $machinery->shift_rent_price) * 100),
                'change_hour' => $machinery->shift_duration,
                'name' => $machinery->name,
                'number' => $number,
                'coordinates' => $this->getCoordinates($city->region->name, $city->name),
            ];
            $images = json_decode($machinery->image);

            $transbaza_machine->update($machine_data);
            $transbaza_machine->generateChpu(true);
            $transbaza_machine->putPhotosFromBase64($images);
            DB::commit();
        } catch (\Exception $exception) {

            \Log::error($exception->getMessage(), $exception->getTrace());
            $this->setFail($id, 'vehicles');
        }


        end2:
        return $this;
    }


    private function getCoordinates($regionName, $city)
    {
        $gmap = new Gmap();
        $coordinates = $gmap->getGeometry('Россия ' . $regionName . ' ' . $city);
        if (!is_array($coordinates)) {
            return null;
        }


       return implode(',', $coordinates);
    }

    private function deleteMachinery($id)
    {
        try {
            DB::beginTransaction();
            $machinery = DB::connection('pgsql')->table('vehicles')->find($id);

            $transbaza_machine = Machinery::where('integration_native_id', $machinery->car_id)->first();

            if (!$transbaza_machine) {
                $this->setFail($id, 'vehicles', 'vehicle not found');

            }
            $transbaza_machine->freeDays()->delete();
            $transbaza_machine->work_hours()->delete();
            $transbaza_machine->forceDelete();

            DB::connection('pgsql')->table('calendars')->where('vehicle_id', $id)->delete();
            DB::connection('pgsql')->table('vehicles')->where('id', $id)->delete();
            DB::commit();
        } catch (\Exception $exception) {

            \Log::error($exception->getMessage(), $exception->getTrace());
            $this->setFail($id, 'vehicles');
        }


        return $this;
    }


    private function setFail($id, $table, string $reason = '')
    {
        if (!isset($this->fails[$table])) {
            $this->fails[$table] = [];

        }
        $this->fails[$table][] = ['id' => $id, 'errorMessage' => $reason];
        return $this;
    }*/

}
