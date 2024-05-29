<?php

namespace App\Http\Controllers\Api;

use App\City;
use App\Machines\Brand;
use App\Machines\Type;
use App\Option;

use App\Role;
use App\Service\EventNotifications;
use App\Service\OrderService;
use App\Service\Widget;
use App\Support\Region;
use App\Widget\WidgetKeyHistory;
use Carbon\Carbon;
use App\User;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Validator;

class WidgetController extends Controller
{
    function getRegions()
    {

        return Region::select(['id', 'name'])->with(
            ['cities' =>
                function ($query) {
                    return $query->onlyName();
                }
            ]
        )->get()->toJson();
    }

    function getWidgetKey($id)
    {
        $widget = Auth::user()->widgets()->findOrFail($id);
        DB::beginTransaction();
        WidgetKeyHistory::create([
            'old_key' => $widget->access_key,
            'widget_id' => $widget->id
        ]);
        $widget->update([
            'access_key' => bin2hex(random_bytes(32))
        ]);
        DB::commit();


        return response()->json([]);
    }

    function getWidgetScript(Request $request, $key)
    {

  /*      $url = parse_url($_SERVER['REQUEST_URI']);
    //    print_r($url);die();
        $arr = [];
        parse_str($url['query'] ?? '', $arr);
        $key = $arr['key'];*/

        $widget = Widget::whereAccessKey($key)->firstOrFail();

      /*  $data = response()->view('widget.script.script', compact('key', 'widget'))->getContent();

        \Storage::disk('public_disk')->put("api/my-widget.js?{$key}", $data);*/

        return view('widget.script.script', compact('key', 'widget'));
    }

    function getRequiredFields(Request $request)
    {

        $needle = [];
        $rules = [
            'email' => 'required|email',
            'region' => 'required|integer',
            'widget_key' => 'exists:widgets,access_key',
            'customer_type' => 'required|in:old,new',
            'city_id' => 'required|integer',
            'amount' => 'integer|min:1',
            'time_type' => 'integer|min:0',
            'date' => 'required|date|after:' . Carbon::now()->format('Y-m-d'),
            'time' => 'required|date_format:H:i',
            'machine_id' => 'required|integer',
            //'type_id' => 'required|integer',
            'address' => 'required|string|max:255',
            'sum' => 'required|numeric|min:1',
            'comment' => 'required|string|min:1',
        ];

        if ($request->customer_type === 'new') {
            $needle = ['name' => 'required|string', 'phone' => 'required|digits:11'];
        }
        return array_merge($rules, $needle);
    }

    function getFieldsMessage()
    {
        return [
            'region.required' => 'Не выбран регион.',
            'region.integer' => 'Не выбран регион.',
            'city_id.integer' => 'Не выбран город.',
            'city_id.required' => 'Не выбран город.',
            'type_id.integer' => 'Не указана категория техники.',
            'type_id.required' => 'Не указана категория техники.',
            'date.required' => 'Не выбрана дата заказа',
            'date.date' => 'Введите корректный формат даты.',
            'date.after' => 'Дата должна быть не раньше, чем сегодня.',
            'date.date_format' => 'Введите корректный формат даты.',
            'days.required' => 'Не указано кол-во смен.',
            'days.integer' => 'Кол-во смен должно быть целым числом.',
            'amount.min' => 'Минимальное кол-во: 1.',
            'type.integer|required' => 'Не выбрана категория техники.',
            'address.required' => 'Не заполнен адрес проведения работ.',
            'comment.required' => 'Не заполнен комментарий к заявке.',
            'comment.max' => 'Максимальное кол-во симоволов: 500.',
            'sum.min' => 'Сумма не может быть отрицательной.',
            'sum.numeric' => 'Сумма должна быть числом.',
            'time_type.integer' => 'Укажите единицу времени.',
            'sum.required' => 'Укажите ваше ограничение по бюджету.',
            'phone.required' => 'Некорректный номер телефона.',
            'phone.integer' => 'Некорректный номер телефона.',
            'phone.min' => 'Некорректный номер телефона.',
            'email.integer' => 'Некорректный Email.',
            'email.email' => 'Некорректный Email',
            'time.required' => 'Некорректное время',
            'days.min' => 'Минимальное кол-во смен: 1.',
            'amount.integer' => '',
        ];
    }

    function makeProposal(Request $request)
    {

        $rules = $this->getRequiredFields($request);
        $request->merge([
            'type' => $request->type_id,
            'machine_type' => 'machine',
        ]);
        $service = new OrderService($request);


        $errors = $service->mergeRequest($rules)->setDaysByAmount()->validateErrors()->getErrors();

        if ($errors) return response()->json($errors, 419);

        $service->search();
        if (Carbon::now() > $service->getStartDate()) {
            $errors['date'] = ['Дата не может быть раньше, чем сегодня.'];
        }
        if ($errors) return response()->json($errors, 419);

        $widget = Widget::whereAccessKey($request->widget_key)->first();

        $email = $request->email;
        $phone = $request->phone;
        DB::beginTransaction();

        if ($request->customer_type === 'old') {
            $user = User::whereEmail($email)->first();
            if (!$user) {
                return response()->json(['email' => [['Пользователь не найден.']]], 419);
            }
        } else {
            $user = User::whereEmail($email)->first();
            $user_phone = User::wherePhone($phone)->first();
            if (!$user && !$user_phone) {
                $user = $this->registerUser($email, $phone);
            } else {
                return response()->json(['email' => [['Такой пользователь уже есть в системе. Переключитесь в режим "Я участник системы"']]], 419);
            }

        }
        $request = $request->all();

        if ($errors) return response()->json($errors, 419);


        $service->forUser($user->id)->createProposal('open', $request['sum']);

        /*  $proposal = Proposal::create([
              'sum' => round($request['sum'] * 100),
              'user_id' => $user->id,
              'date' => $date->format('Y-m-d H:i:s'),
              'days' => $request['amount'],
              'type' => Type::findOrFail($request['type_id'])->id,
              'region_id' => Region::findOrFail($request['region'])->id,
              'city_id' => City::findOrFail($request['city_id'])->id,
              'brand_id' => 0,
              'address' => $request['address'],
              'comment' => $request['comment'],
              'end_date' => $end_date,
              'planned_duration_hours' => null,
              'status' => array_search('open', Proposal::PROP_STATUS),
              'system_commission' => Option::find('system_commission')->value ?? 0
          ]);*/

        if ($widget) {
            $widget_proposal = Proposal\WidgetProposal::create([
                'proposal_id' => $service->created_proposal->id,
                'name' => $request['name'],
                'promo' => $request['promo'] ?? '',
                'widget_id' => $widget->id,
                'new_user' => ($request['customer_type'] === 'old' ? 0 : 1),
                'commission' => Option::get('widget_commission'),
            ]);
        }


        DB::commit();
        (new EventNotifications())->newProposal($service->created_proposal);

        return response()->json([
            'status' => 'success',
            'id' => $service->created_proposal->id
        ]);
    }

    function registerUser($email, $phone)
    {
        $password = str_random(6);
        $user = User::create([
            'email' => $email,
            'phone' => $phone,
            'password' => Hash::make($password),
        ]);

        $role = Role::where('alias', 'customer')->firstOrFail();

        $user->roles()->attach($role->id);

        return $user;
    }

}
