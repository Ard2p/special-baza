<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class LoginController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Login Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles authenticating users for the application and
    | redirecting them to your home screen. The controller uses a trait
    | to conveniently provide its functionality to your applications.
    |
    */

    use AuthenticatesUsers;

    /**
     * Where to redirect users after login.
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
        $this->middleware('guest')->except('logout');
    }

    protected function validator(array $data)
    {
        return Validator::make($data,
            [
                'email' => 'required',
                'password' => 'required',
            ],
            [
                'email.required' => 'Введите email или номер телефона',
                'password.required' => 'Введите пароль.',

            ]
        );
    }

    function authUser(Request $request)
    {
        $errors = $this
            ->validator($request->all())
            ->errors()
            ->getMessages();

        $ref = (\Request::server('HTTP_REFERER'));
        $ref = parse_url($ref);
        $query_parts = [];

        parse_str(($ref['query'] ?? ''), $query_parts);

        if ($errors) return response()->json($errors, 400);

        $input = trimPhone($request->input('email'));

        $by_phone = ['phone' => $input, 'password' => $request->input('password')];

        $by_email = ['email' => $request->input('email'), 'password' => $request->input('password')];

        $auth = Auth::attempt($by_email, 1) ? : Auth::attempt($by_phone, 1);

        if ($auth) {

            $link = (Auth::user()->hasOnlyWidgetRole()) ? route('home_widget') : url()->previous(); //'/' . Auth::user()->getCurrentRoleName() . '/dashboard';
            if(isset($query_parts['redirect_back'])){
                $link = $query_parts['redirect_back'];
            }
            return response()->json(['message' => 'авторизация успешна', 'link' => $link], 200);

        }
        return response()->json(['password' => [trans('validation.incorrect_login_or_pass')]], 400);
    }

}
