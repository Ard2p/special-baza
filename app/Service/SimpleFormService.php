<?php

namespace App\Service;


use App\Role;
use App\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class SimpleFormService
{
    private $request;
    public $created_user;
    public $created_user_password;
    private $errors = [];
    private $selected_user;
    private $submitted_form;


    public function __construct(Request $request = null)
    {
        $request = $request ?: request();
        if ($request->filled('_phone')) {
            $request->merge([
                '_phone' => User::trimPhone($request->_phone)
            ]);
        }
        if(Auth::check()){
            $this->setActionUser(Auth::id());
        }
        if($request->filled('_selected')){
            $this->setActionUser($request->_selected);
        }

        $this->request = $request;
    }

    private function validateUserRegister($data)
    {
        $rules = [

            'simple_proposal_id' => 'required|exists:simple_proposals,id',
            'g-recaptcha-response' => 'required|captcha',
        ];
        $auth_rules = [
            '_email' => 'required|string|email|max:255|unique:users,email',
            '_phone' => 'required|numeric|digits:11|unique:users,phone',
        ];
        if (!Auth::check() && !$this->selected_user) {
            $rules = array_merge($rules, $auth_rules);
        }
        return Validator::make($data, $rules,
            [
                '_email.required' => 'Поле Email обязательно для заполнения.',
                'accept_personal.required' => 'Необходимо принять.',
                'accept_rules.required' => 'Необходимо принять.',
                '_email.email' => 'Некорректный email.',
                '_email.unique' => 'Такой email уже существует',
                '_phone.unique' => 'Такой телефон уже существует',
                '_phone.required' => 'Поле телефон обязательно для заполнения.',
                '_phone.digits' => 'Некорректный телефон',
                'password.required' => 'Поле пароль обязательно для заполнения.',
                'password.confirmed' => 'Пароли не совпадают',
                'password.min' => 'Пароль не менее 6 символов'

            ]
        );
    }

    private function setActionUser($id)
    {
        $this->selected_user = User::findOrFail($id);
    }

    function acceptSimpleForm()
    {
        $validator = $this->validateUserRegister($this->request->all());
        $validator->fails();
        $rules = $validator->failed();

        $errors = $validator
            ->setAttributeNames(
                ['phone' => 'Телефон'])
            ->errors()
            ->getMessages();

        if (isset($rules['_email']['Unique']) || isset($rules['_phone']['Unique'])) {
            $users = collect([]);

            $user_email = User::whereEmail($this->request->_email)->first();
            if($user_email){
                $users->push($user_email);
            }

            $user_phone = User::wherePhone($this->request->_phone)->first();
            if($user_phone){
                if($user_email && $user_email->id !== $user_phone->id){
                     $users->push($user_phone);
                }elseif (!$user_email){
                    $users->push($user_phone);
                }
            }
            $request = $this->request;
            $errors['alert'] = view('includes.simple_form.render_error', compact('users', 'request'))->render();
        }
        if ($errors) {
            $this->errors = $errors;
            return $this;
        }

        DB::beginTransaction();
        $this->registerUser($this->request->_email, $this->request->_phone)->createSimpleForm();
        DB::commit();
        return $this;
    }

    function getErrors()
    {
        return $this->errors;
    }

    function getSubmitForm()
    {
        return $this->submitted_form;
    }

    private function createSimpleForm($user = null)
    {
       $this->submitted_form = SubmitSimpleProposal::create([
            'email' => $this->selected_user ? $this->selected_user->email : $this->request->_email,
            'phone' => $this->selected_user ? $this->selected_user->phone : $this->request->_phone,
            'comment' => $this->request->comment,
            'simple_proposal_id' => $this->request->simple_proposal_id,
            'region_id' => $this->request->region_id ?: 0,
            'city_id' => $this->request->city_id ?: 0,
            'type_id' => $this->request->type_id ?: 0,
            'user_id' => $this->selected_user ? $this->selected_user->id : $this->created_user->id,
            'url' => $this->request->input('url'),
            'proposal_id' => 0,
        ]);

        return $this;
    }

    private function registerUser($email, $phone)
    {
        if ($this->selected_user) {

            return $this;
        }
        $password = str_random(6);
        $user = User::create([
            'email' => $email, //$this->request->email,
            'phone' => $phone, // $this->request->phone,
            'password' => Hash::make($password),
        ]);

        $role = Role::where('alias', 'customer')->firstOrFail();

        $user->roles()->attach($role->id);

        $this->created_user = $user;
        $this->created_user_password = $password;

        return $this;
    }
}