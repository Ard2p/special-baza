<?php

namespace Modules\RestApi\Http\Controllers;

use App\City;
use App\Helpers\RequestHelper;
use App\Role;
use App\Service\RequestBranch;
use App\Service\Sms;
use App\Service\SpamCheck\StopForumSpam;
use App\System\SpamEmail;
use App\User;
use App\User\EntityRequisite;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Modules\CompanyOffice\Services\CompaniesService;
use Modules\CorpCustomer\Entities\CorpBrand;
use Modules\CorpCustomer\Entities\CorpCompany;
use Modules\CorpCustomer\Entities\InternationalLegalDetails;
use Modules\RestApi\Entities\Auth\AuthHash;
use Modules\RestApi\Entities\Auth\GuestPhoneConfirm;
use Modules\RestApi\Jobs\SendSms;

class AuthController extends Controller
{

    public function __construct(Request $request)
    {

        $request->merge([
            'login' => $request->input('login') ? strtolower($request->input('login')) : ''
        ]);
    }

    function login(Request $request)
    {
        $request->validate([
            //  'g-recaptcha-response' => 'required|captcha',
            'login' => 'required|max:255',
            'password' => "required|string",

        ], [
            'login.required' => 'Введите Email или номер телефона',
            'g-recaptcha-response.required' => 'Пройдите проверку!',
            'g-recaptcha-response.captcha' => 'Пройдите проверку!',
        ]);


        $client = DB::table('oauth_clients')
            ->where('password_client', true)
            ->first();

        if (!$client) {
            return response()->json([
                'message' => 'Laravel Passport is not setup properly.',
                'status' => 500
            ], 500);
        }
        $domain = RequestHelper::requestDomain();
        $login = isValidPhoneNumber($request->login,  app(RequestBranch::class)->getDomain()->options['phone_digits']) ? trimPhone($request->login) : $request->login;

        $user = User::where('email', $login)->orWhere('phone', $login)->first();

        if(!$user || !Hash::check( $request->password, $user->password)) {
            return response()->json([
                'password' => [trans('validation.incorrect_login_or_pass')],
            ], 422);
        }

        return response()->json([
            'token' => $user->createToken('Laravel Password Grant Client')->accessToken,
            'user' => new \Modules\RestApi\Transformers\User($user),
        ]);
    }

    function authHash(Request $request)
    {
       return AuthHash::getAuthDataByHash($request->hash);
    }


    function getUser()
    {
        /*   $user =  Auth::user()->isSuperAdmin()
               ? Auth::user()->load('ya_call')
               : Auth::user();
           $user->load('contractor_balance');*/
        return new \Modules\RestApi\Transformers\User(Auth::user());
    }

    function register(Request $request)
    {

        $rules = [
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:6|max:30',
            'hash' => 'required|exists:guest_phone_confirms,hash',
            'city_id' => 'required|exists:cities,id',
            'account_type' => 'required|in:company,individual',
            'contact_person' => 'nullable|string|max:255',
            /*'country_id' => 'required|exists:countries,id',*/
            /* 'g-recaptcha-response' => 'required|captcha',*/
            /*'region_id' => 'required|exists:regions,id',*/
        ];
        if($request->input('account_type') === 'company'){
            $rules['company_name'] = 'required|string|min:2|max:255';
        }

        $request->validate( $rules,
            [
                'email.required' => 'Поле email обязательно для заполнения.',
                'accept_personal.accepted' => 'Необходимо принять.',
                'accept_rules.accepted' => 'Необходимо принять.',
                'email.email' => 'Некорректный email.',
                'email.unique' => 'Такой email уже существует',
                'phone.unique' => 'Такой телефон уже существует',
                'phone.required' => 'Поле телефон обязательно для заполнения.',
                'phone.digits' => 'Поле телефон обязательно для заполнения.',
                'password.required' => 'Поле пароль обязательно для заполнения.',
                'password.confirmed' => 'Пароли не совпадают',
                'account_type.required' => 'Тип аккаунта обязательное поле',
                'password.min' => 'Пароль не менее 6 символов',
                'region_id.required' => 'Выберите родной регион.',
                'city_id.required' => 'Выберите родной город.'

            ]
        );

        DB::beginTransaction();

        $city = City::findOrFail($request->input('city_id'));
        $guest_phone = GuestPhoneConfirm::whereHash($request->input('hash'))->firstOrFail();
        $user = User::create([
            'email' => $request->input('email'),
            'phone' => $guest_phone->phone,
            'phone_confirm' => 1,
            'contractor_alias_enable' => 1,
            'account_type' => $request->input('account_type'),
            'password' => Hash::make($request->input('password')),
            'country_id' => $city->region->country_id,
            'native_region_id' => $city->region_id,
            'native_city_id' => $city->id,
            'contact_person' => $request->input('contact_person'),
        ]);

        $service = CompaniesService::createCompany($user, RequestHelper::requestDomain()->id, $request->input('company_name'));

        $service->createBranch($request->input('company_name'), $city->region_id, $city->id);

        DB::commit();

        $spamCheck = new StopForumSpam();
        if ($spamCheck->isSpamEmail($request->input('email'))) {
            SpamEmail::firstOrCreate([
                'email' => $request->input('email'),
                'spam_system' => 'StopForumSpam',
            ]);
        }

        return response()->json([
            'message' => trans('auth.register_success')
        ], 200);

    }

    function logout()
    {
        if (!Auth::guard('api')->check()) {
            return \response('OK');
        }
        $accessToken = Auth::guard('api')->user()->token();

        DB::table('oauth_refresh_tokens')
            ->where('access_token_id', $accessToken->id)
            ->update([
                'revoked' => true
            ]);

        $accessToken->revoke();
    }

    function preConfirmPhone(Request $request)
    {
        $request->validate([
            'phone' => 'required|unique:users,phone|digits:' .  RequestHelper::requestDomain()->options['phone_digits']
        ]);

        DB::beginTransaction();
        $check = GuestPhoneConfirm::wherePhone($request->input('phone'))->first();

        if ($check) {

            if (!$check->checkActual()) {

                $check->delete();

            } else {
                return response()->json(['seconds' => 120 - now()->diffInSeconds($check->created_at)]);
            }
        }

        $code = random_int(100000, 999999);

        $guest = GuestPhoneConfirm::create([
            'phone' => $request->input('phone'),
            'code' => $code,
            'hash' => str_random(8)
        ]);


        dispatch(new SendSms($guest->phone, $guest->code));


        DB::commit();

        return response()->json(['seconds' => 120]);
    }

    function checkCode(Request $request)
    {
        $request->validate([
            'phone' => 'required|unique:users,phone|digits:' .  RequestHelper::requestDomain()->options['phone_digits'],
            'code' => 'required|digits:6|integer',
        ]);


        $guest = GuestPhoneConfirm::query()->wherePhone($request->input('phone'))
            ->whereCode($request->input('code'))
            ->firstOrFail();

        return response()->json(['hash' => $guest->hash]);
    }
}
