<?php

namespace App\Http\Controllers\Auth;

use App\Helpers\RequestHelper;
use App\Role;
use App\Service\SpamCheck\StopForumSpam;
use App\Support\Region;
use App\System\SpamEmail;
use App\User;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Validator;
use Illuminate\Foundation\Auth\RegistersUsers;

class RegisterController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Register Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles the registration of new users as well as their
    | validation and creation. By default this controller uses a trait to
    | provide this functionality without requiring any additional code.
    |
    */

    use RegistersUsers;

    /**
     * Where to redirect users after registration.
     *
     * @var string
     */
    protected $redirectTo = '/';

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('guest');
    }

    public function showRegistrationForm()
    {
        $regions = Region::whereCountry('russia')->get();
        return view('auth.register', compact('regions'));
    }


    /**
     * Get a validator for an incoming registration request.
     *
     * @param array $data
     * @return \Illuminate\Contracts\Validation\Validator
     */
    protected function validator(array $data)
    {
        return Validator::make($data, [
            'email' => 'required|string|email|max:255|unique:users',
            'phone' => 'required|numeric|digits:' .  RequestHelper::requestDomain()->options['phone_digits'] .'|unique:users',
            'password' => 'required|string|min:6|confirmed',
            'account_type' => 'required|in:customer,contractor',
            'accept_personal' => 'required|in:1',
            'accept_rules' => 'required|in:1',
            'country_id' => 'required|exists:countries,id',
            'g-recaptcha-response' => 'required|captcha',
            'region_id' => 'required|exists:regions,id',
            'city_id' => 'required|exists:cities,id',
        ],
            [
                'email.required' => 'Поле email обязательно для заполнения.',
                'accept_personal.required' => 'Необходимо принять.',
                'accept_rules.required' => 'Необходимо принять.',
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
    }

    /**
     * Create a new user instance after a valid registration.
     *
     * @param array $data
     * @return \App\User
     */
    protected function create(array $data)
    {
        return User::create([
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
        ]);
    }

    /**
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function register(Request $request)
    {
        if ($request->has('phone')) {
            $request->merge(
                ['phone' => (int)str_replace(
                    [')', '(', ' ', '+', '-'],
                    '',
                    $request->input('phone'))
                ]);
        }
        $errors = $this
            ->validator($request->all())
            ->errors()
            ->getMessages();

        if (isset($errors['g-recaptcha-response'])) {
            $errors['captcha_error'] = ['Пройдите проверку!'];
            unset($errors['g-recaptcha-response']);
        }

        if ($errors) return response()->json($errors, 400);

        DB::beginTransaction();


        $user = User::create([
            'email' => $request->input('email'),
            'phone' => $request->input('phone'),
            'contractor_alias_enable' => 1,
            'account_type' => $request->input('account_type'),
            'password' => Hash::make($request->input('password')),
            'country_id' => $request->input('country_id'),
            'native_region_id' => $request->input('region_id'),
            'native_city_id' => $request->input('city_id'),
        ]);

        $role = Role::where('alias',
            (($request->input('account_type') == 'contractor')
                ? 'performer'
                : 'customer')
        )->firstOrFail();
        $user->roles()->attach($role->id);

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

}
