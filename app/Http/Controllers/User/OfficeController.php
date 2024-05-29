<?php

namespace App\Http\Controllers\User;

use App\Article;
use App\City;
use App\Finance\FinanceTransaction;
use App\Machinery;
use App\Machines\Type;
use App\Service\EventNotifications;
use App\Support\Region;
use App\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class OfficeController extends Controller
{
    function index()
    {
        $articles = Article::where('is_publish', 1)->where('is_static', 0)->where('is_article', 0)->orderBy('created_at', 'desc')->paginate(10);

        return view('user.customer.dashboard', compact('articles'));

    }

    function getNotification(Request $request)
    {
        $noty = Auth::user()->notification_histories();

        if($request->filled('date_from')){
            $noty->whereDate('created_at', '>=',
                Carbon::parse($request->date_from)->format('Y-m-d'));
        }
        if($request->filled('date_to')){
            $noty->whereDate('created_at', '<=',
                Carbon::parse($request->date_from)->format('Y-m-d'));
        }
        if($request->filled('type')){
            $noty->whereType(
                ($request->type === 'sms' ? 'sms' : 'email')
            );
        }

        return $noty->get();
    }

    function checkAuth()
    {
        return Auth::check() ? response()->json(Auth::user())
            : response()->json([], 419);
    }

    function profile(Request $request)
    {
        if($request->filled('get_notification_history')){

            return response()->json(['data' => $this->getNotification($request)]);
        }
        $regions = Region::whereCountryId(Auth::user()->country_id)->with('cities')->get();
        $requisite = Auth::user()->getActiveRequisite();
        return view('user.profile', compact('regions', 'requisite'));
    }


    function syncRoles(Request $request): void
    {
        $arr = [];
        $arr[] = $request->has('performer') ? 'performer' : '';
        $arr[] = $request->has('customer') ? 'customer' : '';
        $arr[] = $request->has('widget') ? 'widget' : '';
        $arr[] = Auth::user()->checkRole('admin') ? 'admin' : '';
        $arr[] = Auth::user()->checkRole('content_admin') ? 'content_admin' : '';
        $roles = Auth::user()->getRolesId($arr);
        Auth::user()->roles()->sync($roles);

        if ($request->has('performer') && !$request->has('customer')) {
            Auth::user()->switchRole('contractor');
        } elseif (!$request->has('performer') && $request->has('customer')) {
            Auth::user()->switchRole('customer');
        }
    }

    function changeProfile(Request $request)
    {
        if ($request->has('phone')) {
            $request->merge(
                ['phone' => User::trimPhone($request->input('phone'))
                ]);
        }

        $rule = (!$request->has('password'))
            ? Auth::user()->getUpdateFields($request)
            : User::UPDATE_PASSWORD;
        if(!Auth::user()->email){
            $rule = array_merge($rule, ['email' => 'required|email|unique:users,email']);
        }
        $errors = Validator::make($request->all(), $rule, [
            'phone.unique' => 'Телефон уже зарегистрирован в системе',
            'email.unique' => 'Email уже зарегистрирован в системе',
            'region.required' => 'Выберите родной регион.',
            'city_id.required' => 'Выберите родной город.'

        ])
            ->errors()
            ->all();

        if ($errors) return response()->json($errors, 419);

        if ($request->has('password')) {
            Auth::user()->update([
                'password' => Hash::make($request->input('password'))
            ]);
        } else {
            if ($request->has('performer') || $request->has('customer')) {
                $this->syncRoles($request);
            } else {
                return response()->json(['Выберите хотя бы одну роль.'], 419);
            }
            /* $message = ($request->has('performer') && Auth::user()->account_type == 'individual')
                 ? true
                 : false;*/
            $current_region = Auth::user()->native_region_id;
            $current_city = Auth::user()->native_city_id;

            $region = Region::findOrFail($request->input('region'));
            $arr_fields = [
                'native_region_id' => $region->id,
                'native_city_id' => $region->cities()->findOrFail($request->input('city_id'))->id,
                'phone' => $request->phone,
                'enable_ticker' => $request->enable_ticker ? 1 : 0,

            ];
            if($request->phone != Auth::user()->phone ){
                $arr_fields = array_merge($arr_fields, [
                    'phone_confirm' => 0
                ]);
            }
            if(!Auth::user()->email){
                $arr_fields = array_merge($arr_fields, [
                    'email' => $request->email
                ]);
            }
            Auth::user()->update($arr_fields);


           /* if (Auth::user()->isContractor()) {
                if ($request->filled('contractor_alias_enable')) {
                    Auth::user()->contractor_alias = urlencode($request->contractor_alias);
                    Auth::user()->contractor_alias_enable = 1;
                } else {
                    Auth::user()->contractor_alias_enable = 0;
                }
                /*Auth::user()->is_regional_representative = $request->has('is_regional_representative') ? 1 : 0;
                Auth::user()->is_promoter = $request->has('is_promoter') ? 1 : 0;
                Auth::user()->promo_code = $request->input('promo_code');
                Auth::user()->save();
            }*/
        }
        return response()->json([
            'message' => 'Успешно сохранено. ' . (($message ?? false) === true
                    ? '<br>Испонитель может быть только Юр. лицом. Обновите пожалуйста Ваши реквизиты.'
                    : '')
        ]);
    }

    public function loadAvatar(Request $request)
    {
        $errors = Validator::make($request->all(), [
            'image' => 'required|image'
        ])
            ->errors()
            ->all();

        if ($errors) return response()->json($errors, 419);
        $user = \Auth::user();

        Storage::disk()->deleteDirectory('/avatars/' . $user->id);
        $file = $request->file('image');
        $saved_avatar = Storage::put('avatars/' . auth()->user()->id . "/{$file->hashName()}", $file);
        return response()->json(['saved_avatar' => $saved_avatar], 200);
    }

    public function delAvatar()
    {
        $user = \Auth::user();

        Storage::disk()->deleteDirectory('/avatars/' . $user->id);
        return response()->json([]);
    }

    function changeRole(Request $request)
    {
        $errors = Validator::make($request->all(), [
            'role' => 'required|in:customer,contractor'
        ])
            ->errors()
            ->all();
        if ($errors) return response()->json($errors, 419);
        $role = $request->input('role');
        if (Auth::user()->checkRole(
            ($role === 'contractor') ? 'performer' : $role
        )) {
            Auth::user()->switchRole($request->input('role'));
        }
        return redirect()->to($role . '/dashboard');
    }


    function acceptToken($token)
    {
        $model = User\UserConfirm::where('token', $token)->firstOrFail();

        $user = User::where('email', $model->email)->firstOrFail();
        $user->update([
            'active' => 1,
            'email_confirm' => 1
        ]);

        $model->delete();

        return (Auth::check())
            ? ($user->hasOnlyWidgetRole()
                ? redirect()->route('home_widget')
                : redirect()->route('profile_index'))
            : redirect()->route('login');

    }

    function resendToken()
    {
        if (Auth::user()->email_confirm) {
            return response()->json(['errors' => ['Email уже подтвержден']], 400);
        }
        $check = DB::transaction(function () {
            return Auth::user()->sendNewToken();
        });
        if ($check !== true) {
            return response()->json(['errors' => ['Отправить повторное письмо можно через ' . $check . ' минут.']], 400);

        } else {
            return response()->json(['message' => 'Подтверждение отправлено',]);
        }
    }

    function resendSmsToken(Request $request)
    {
        if ($request->has('phone')) {
            $request->merge(
                ['phone' => (int)str_replace(
                    [')', '(', ' ', '+', '-'],
                    '',
                    $request->input('phone'))
                ]);
        }

        $errors = Validator::make($request->all(), [
            'phone' => 'required|integer|min:9|unique:users,phone,' . Auth::user()->id,
        ], [
            'phone.unique' => 'Телефон уже зарегистрирован в системе',
        ])
            ->errors()
            ->all();

        if ($errors) return response()->json($errors, 419);



        if (Auth::user()->phone_confirm) {
            return response()->json(['errors' => ['Телефон уже подтвержден']], 400);
        }
        $form = view('user.accept_sms')->render();
        $check = DB::transaction(function () use ($request) {
            return Auth::user()->sendSmsToken($request->input('phone'));
        });
        if ($check !== true) {
            return response()->json(['errors' => ['Отправить повторное сообщение можно через ' . $check . ' минут.']], 400);

        } else {
            return response()->json(['message' => 'Подтверждение отправлено', 'form' => $form]);
        }
    }

    function smsConfirm(Request $request)
    {
        $confirm = User\PhoneConfirm::whereUserId(Auth::user()->id)
            ->whereToken($request->code)
            ->first();
        if($confirm){
            Auth::user()->update([
                'phone' => $confirm->phone,
                'phone_confirm' => 1,
            ]);

            return response()->json(['message' => 'Телефон подтвежден.']);
        }
        return response()->json(['code' => ['Некорректный код.']], 419);
    }

    function freeze()
    {
        if (Auth::user()->is_freeze) {
            Auth::user()->unfreeze();

            Session::flash('email_confirm', 'Аккаунт разморожен.');
        } else {
            Auth::user()->freeze();
            Session::flash('email_verify', ['Аккаунт заморожен.']);
        }
    }

    function changeLanguage($lng)
    {
        $errors = Validator::make(['lng' => $lng], [
            'lng' => 'required|in:ru,en',
        ])
            ->errors()
            ->all();

        if ($errors) return response()->json($errors, 419);

        $cookie = \Cookie::forever('current_locale', $lng);

        return redirect()->back()->withCookie($cookie);
    }

    function changeEditMode()
    {
        if(!Session::exists('editable_mode')){
            Session::put('editable_mode', 1);
        }else{
            Session::forget('editable_mode');
        }

        return redirect()->back();
    }



    function closeTicker()
    {
        Auth::user()->closeTicker();
    }
}
