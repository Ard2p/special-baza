<?php

namespace Modules\Dispatcher\Http\Controllers;

use App\User;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Modules\AdminOffice\Entities\YandexPhoneCredential;

class DispatcherController extends Controller
{


    function yandexPhoneAccount(Request $request)
    {
       $request->validate( [
            'login' => 'required|string|max:255',
            'password' => 'required|string|max:255',
        ]);

        $user = auth()->user();

        $account = $user->ya_call ?: new YandexPhoneCredential();

        $account->fill([
            'login' => $request->input('login'),
            'password' => $request->input('password'),
            'enable' => filter_var($request->input('enable'), FILTER_VALIDATE_BOOLEAN),
        ]);

        $user->ya_call()->save($account);

        return response()->json();
    }

    function getYandexPhoneAccount()
    {
        return Auth::user()->ya_call;
    }
}
