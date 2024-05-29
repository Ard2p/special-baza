<?php

namespace Modules\Profiles\Http\Controllers;

use App\City;
use App\Helpers\RequestHelper;
use App\Machinery;
use App\Machines\Type;
use App\Notifications\ResetPassword;
use App\Service\EventNotifications;
use App\Service\Widget;
use App\Support\Region;
use App\User;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Modules\AdminOffice\Entities\Marketing\Mailing\Template;
use Modules\Orders\Entities\OrderDocument;
use Modules\Profiles\Entities\PasswordReset;
use Modules\RestApi\Emails\DBMail;
use Spatie\EloquentSortable\Sortable;

class ProfilesController extends Controller
{

    private function validateUpdate(
        Request $request,
        User    $user)
    {

        $rule = [
            'phone'          => 'required|numeric|digits:' . RequestHelper::requestDomain()->options['phone_digits'] . '|unique:users,phone,' . $user->id,
            'email'          => 'required|email|unique:users,email,' . $user->id,
            'region_id'      => 'required|exists:regions,id',
            'contact_person' => 'required|string|max:255',
            'city_id'        => [
                'required',
                'integer',
                Rule::exists('cities', 'id')->where('region_id', $request->region_id
                    ?: 0)
            ],
        ];
        if ($request->filled('password')) {
            $rule['password'] = 'required|string|min:6|confirmed';
        }

        return Validator::make($request->all(), $rule, [
            'phone.unique'       => trans('transbaza_register.validator_phone_exists'),
            'email.unique'       => trans('transbaza_register.validator_email_exists'),
            'region_id.required' => trans('transbaza_register.validator_select_region'),
            'city_id.required'   => trans('transbaza_register.validator_select_city')

        ])->setAttributeNames([
            'contact_person' => trans('transbaza_proposal_search.contact_person')
        ])
            ->errors()
            ->getMessages();
    }

    private function update_password(
        Request $request,
                $user)
    {
        $user->update([
            'password' => \Hash::make($request->input('password'))
        ]);
    }

    function updateProfile(Request $request)
    {
        $user = Auth::user();

        $request->merge(['phone' => trimPhone($request->phone)]);

        $errors = $this->validateUpdate($request, $user);

        if ($errors) return response()->json($errors, 400);

        DB::beginTransaction();
        if ($request->filled('password')) {
            $this->update_password($request, $user);
        }
        $current_region = $user->native_region_id;
        $current_city = $user->native_city_id;


        $arr_fields = [
            'native_region_id' => $request->input('region_id'),
            'native_city_id'   => $request->input('city_id'),
            'phone'            => $request->phone,
            'enable_ticker'    => $request->enable_ticker
                ? 1
                : 0,
            'email'            => $request->email,
            'contact_person'   => $request->contact_person,

        ];
        if ($request->phone != $user->phone) {
            $arr_fields = array_merge($arr_fields, [
                'phone_confirm' => 0
            ]);
        }
        if ($user->email !== $request->email) {
            $arr_fields = array_merge($arr_fields, [
                'email_confirm' => 0
            ]);
        }
        Auth::user()->update($arr_fields);

        DB::commit();
        if (!$current_region && !$current_city) {
            (new EventNotifications())->newUser(Auth::user());
        }

        return \Modules\RestApi\Transformers\User::make($user);
    }

    function getUserProfile($alias)
    {
        $user = User::whereContractorAlias($alias)->firstOrFail();

        return view('user.public_profile', compact('user'));
    }


    function contractorPublicPage(
        Request $request,
                $alias)
    {

        $user = User::with([
            'machines' => function ($q) use
            (
                $request
            ) {
                if ($request->filled('type_id')) {
                    $q->whereType($request->type_id);
                }
                if ($request->filled('region')) {
                    $q->whereRegionId($request->region);
                }
                if ($request->filled('city_id')) {
                    $q->whereCityId($request->city_id);
                }
            }
        ])
            // ->whereContractorAliasEnable(1)
            ->whereContractorAlias($alias)->firstOrFail();


        $machines = $user->machines;


        $regions =
            Region::whereCountry('russia')->whereHas('machines', function ($q) use
            (
                $user
            ) {
                if ($user) {
                    $q->whereUserId($user->id);
                }

            })->get();

        $types =
            Type::whereHas('machines', function ($q) use
            (
                $user
            ) {
                if ($user) {
                    $q->whereUserId($user->id);
                }
            })->get();

        $initial_type =
            $request->filled('type_id')
                ? Type::find($request->type_id)
                : '';
        $initial_region =
            ($request->filled('region')
                ? Region::find($request->region)
                : '');
        $checked_city_source =
            ($request->filled('city_id')
                ? City::find($request->city_id)
                : '');


        $time_type = Widget::$time_type;
        return view('user.machines.public_page', compact('machines', 'time_type', 'user', 'types', 'regions', 'initial_region', 'initial_type', 'checked_city_source'));
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
                [
                    'phone' => trimPhone($request->input('phone'))
                ]);
        }

        $request->validate([

            'phone' => 'required|integer|min:9|unique:users,phone,' . Auth::user()->id,
        ],
            [
                'phone.unique' => trans('transbaza_register.validator_phone_exists'),
            ]);

        if (Auth::user()->phone_confirm && $request->phone === Auth::user()->phone) {
            return response()->json(['errors' => ['Телефон уже подтвержден']], 400);
        }


        $check =
            DB::transaction(function () use
            (
                $request
            ) {
                return Auth::user()->sendSmsToken($request->input('phone'));
            });
        if ($check !== true) {
            return response()->json(['errors' => ['Отправить повторное сообщение можно через ' . $check . ' минут.']], 400);

        } else {
            return response()->json(['message' => 'Подтверждение отправлено']);
        }
    }

    function smsConfirm(Request $request)
    {
        $confirm = User\PhoneConfirm::whereUserId(Auth::user()->id)
            ->whereToken($request->code)
            ->first();
        if ($confirm) {
            Auth::user()->update([
                'phone'         => $confirm->phone,
                'phone_confirm' => 1,
            ]);

            return response()->json(['message' => 'Телефон подтвежден.']);
        }
        return response()->json(['errors' => ['Некорректный код.']], 419);
    }

    function resetPassowrd(Request $request)
    {
        $request->validate([
            'email' => 'required|email|exists:users,email',
        ], [
            'phone.unique' => 'Телефон уже зарегистрирован в системе',
        ]);

        $user = User::whereEmail($request->email)->firstOrFail();
        $resets = PasswordReset::whereEmail($user->email)->where('created_at', '>=', now()->subMinutes(15))->first();
        if ($resets) {
            return \response()->json(['email' => ['Письмо уже было отправлено. Повторная отправка возможна через 15 минут после предыдущей.']], 400);
        }
        $token = str_random(30);
        PasswordReset::create([
            'token'      => $token,
            'email'      => $user->email,
            'created_at' => now()
        ]);

        $template = Template::getTemplate(Template::TYPE_RESTORE_PASSWORD, RequestHelper::requestDomain()->id);

        $user->sendEmailNotification(new DBMail($template, ['link' => origin('password-reset', ['token' => $token], RequestHelper::requestDomain())]), false);

        return response('ОК');
    }

    function checkToken(Request $request)
    {
        $token = PasswordReset::whereToken($request->input('token'))->first();

        return response()->json([], $token
            ? 200
            : 400);

    }

    function changeResetPassword(Request $request)
    {
        $errors = Validator::make($request->all(), [
            'login'    => 'required|string|email',
            'password' => 'required|string|min:6|max:20|confirmed',
            'token'    => 'required|string'
        ])->errors()->getMessages();

        if ($errors) return response()->json($errors, 400);

        $passwordReset = PasswordReset::where([
            ['token', $request->token],
            ['email', $request->login]
        ])->first();

        $fail_response = response()->json([
            'login' => ['Некорректный Email или истек срок ссылки.']
        ], 404);

        if (!$passwordReset) {
            return $fail_response;
        }

        $user = User::where('email', $passwordReset->email)->first();

        if (!$user) {
            return $fail_response;
        }
        DB::beginTransaction();
        $user->update([
            'password' => Hash::make($request->password)
        ]);

        PasswordReset::whereEmail($user->email)->delete();

        DB::commit();
        return response('OK');
    }

    function confirmEmail(Request $request)
    {
        $token = $request->input('token');
        $model = User\UserConfirm::where('token', $token)->firstOrFail();

        DB::beginTransaction();
        $user = User::where('email', $model->email)->firstOrFail();
        $user->update([
            'active'        => 1,
            'email_confirm' => 1
        ]);

        $model->delete();
        DB::commit();

        return response('OK');
    }

    function deleteDocument($id)
    {
        $document = OrderDocument::currentUser()->findOrFail($id);

        $document->delete();

    }

    public function upDocument($id)
    {
  //OrderDocument::query()->where('order_column', 0)->get()->groupBy(fn($doc) => $doc->order_id.$doc->order_type.$doc->ext_type)
  //    ->each(function ($documents) {
  //       $i = 0;
  //       foreach ($documents as $document) {
  //           $document->update([
  //               //'ext_type' => last(explode('.', $document->url)),
  //               'order_column' => ++$i,
  //           ]);
  //       }
  //    });
  /** @var OrderDocument $document */
  $document = OrderDocument::query()->findOrFail($id);

  $document->moveOrderUp();
    }

    public function downDocument($id)
    {
        /** @var OrderDocument $document */
        $document = OrderDocument::query()->findOrFail($id);
       // if ($document->{$document->determineOrderColumnName()} == 0) {
       //     $document->setHighestOrderNumber();
       // }
        $document->moveOrderDown();

    }


}
