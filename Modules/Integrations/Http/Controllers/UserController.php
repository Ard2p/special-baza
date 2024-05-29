<?php

namespace Modules\Integrations\Http\Controllers;

use App\Helpers\RequestHelper;
use App\User;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class UserController extends Controller
{

    private $integration_id;

    public function __construct()
    {
          $this->setIntegration();
    }

    private function setIntegration()
    {
          $this->integration_id = Auth::user() && Auth::user()->adminIntegation ? Auth::user()->adminIntegation->id  : null;
    }

    private function validateUser($data)
    {
        return Validator::make($data, [
            'phone' => 'required|integer|unique:users,phone|digits:' . RequestHelper::requestDomain()->options['phone_digits'],
            'email' => 'required|unique:users,email'
        ]);
    }

    private function mapUser($user, $with_machines = false)
    {
        $arr =  [
            'id' => $user->id,
            'email' => $user->email,
            'phone' => $user->phone,
        ];
        if($with_machines) {
            $arr = array_merge($arr, ['vehicles' => $user->vehicles]);
        }

        return $arr;

    }


    private function validateUpdateUser($data, $user)
    {
        return Validator::make($data, [
            'phone' => "required|integer|unique:users,phone,{$user->id}|digits:" .  RequestHelper::requestDomain()->options['phone_digits'],
            'email' => "required|unique:users,email,{$user->id}"
        ]);
    }

    function registerUser(Request $request)
    {
        $phone = User::trimPhone($request->phone);

        $validate_errors = $this->validateUser($request->json()->all())->errors()->getMessages();

        if ($validate_errors) {
            return response()->json($validate_errors, 400);
        }
        $integration_id = Auth::user()->adminIntegation->id;

        try {
            DB::beginTransaction();

            $new_user = User::register($request->email, $phone, 'performer');
            $new_user->integrations()->attach($integration_id);

            DB::commit();
        } catch (\Exception $exception) {
            \Log::error($exception->getMessage());
            return response()->json(['something went wrong'], 400);
        }

        return \response()->json(
            $this->mapUser($new_user)
        );
    }


    function updateUser(Request $request, $id)
    {
        $user = User::currentIntegration()->findOrFail($id);
        $phone = User::trimPhone($request->phone);

        $validate_errors = $this->validateUpdateUser($request->json()->all(), $user)->errors()->getMessages();
        if ($validate_errors) {
            return response()->json($validate_errors, 400);
        }

        try {
            DB::beginTransaction();

            $user->update(['email' => $request->email, 'phone' => $phone]);

            DB::commit();
        } catch (\Exception $exception) {
            \Log::error($exception->getMessage());
            return response()->json(['something went wrong'], 400);
        }

        return \response()->json(
            $this->mapUser($user)
        );
    }


    function allUsers(Request $request)
    {

        $users = User::currentIntegration();

        $with_vehicle = ($request->filled('with') && $request->with === 'vehicles');

        $with_trashed = ($request->filled('trashed') && $request->trashed);

        if($with_vehicle){
            $users->with('machines');
        }

        if($with_trashed){
            $users->onlyTrashed();
        }

        $users = $users->get();

        return $users->map(function ($user) use ($with_vehicle){
           return $this->mapUser($user, $with_vehicle);
        });
    }

    function getUser(Request $request, $id)
    {
        $user = User::currentIntegration()->findOrFail($id);

        $with_vehicle = ($request->filled('with') && $request->with === 'vehicles');

        return \response()->json(
            $this->mapUser($user, $with_vehicle)
        );
    }

    function deleteUser($id)
    {
        $user = User::currentIntegration()->findOrFail($id);
        DB::beginTransaction();

        foreach ($user->machines as $machine) {
            $machine->delete();
        }
        $user->delete();
        DB::commit();
    }

    function restoreUser($id)
    {
        $user = User::currentIntegration()->onlyTrashed()->findOrFail($id);
        DB::beginTransaction();
        $machines = $user->machines()->onlyTrashed()->get();
        foreach ($machines as $machine) {
            $machine->restore();
        }
        $user->restore();
        DB::commit();

        return \response()->json(
            $this->mapUser($user)
        );
    }


}
