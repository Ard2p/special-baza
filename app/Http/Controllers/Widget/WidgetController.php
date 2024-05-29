<?php

namespace App\Http\Controllers\Widget;

use App\City;
use App\Directories\TransactionType;
use App\Finance\FinanceTransaction;
use App\Machinery;
use App\Machines\Type;
use App\Option;
use App\Role;
use App\Service\Widget;
use App\Support\Region;
use App\User;
use App\Widget\WidgetRequestHistory;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class WidgetController extends Controller
{

    protected function validator(array $data)
    {
        return Validator::make($data, [
            'email' => 'required|string|email|max:255|unique:users',
            'phone' => 'required|int|min:11|unique:users',
            'password' => 'required|string|min:6|confirmed',
            'accept_personal' => 'required|in:1',
            'accept_rules' => 'required|in:1',
        ],
            [
                'email.required' => trans('transbaza_widgets.validate_email'),
                'accept_personal.required' => trans('transbaza_widgets.validate_need_accept'),
                'accept_rules.required' => trans('transbaza_widgets.validate_need_accept'),
                'email.email' => trans('transbaza_widgets.validate_incorrect_email'),
                'email.unique' => trans('transbaza_widgets.validate_email_exists'),
                'phone.unique' => trans('transbaza_widgets.validate_phone_exists'),
                'phone.required' => trans('transbaza_widgets.validate_phone_required'),
                'password.required' => trans('transbaza_widgets.validate_password'),
                'password.confirmed' => trans('transbaza_widgets.validate_password_confirm'),
                'password.min' => trans('transbaza_widgets.validate_password_short')

            ]
        );
    }

    function index()
    {
        return view('widget.home');
    }

    function show($id)
    {
        $widget = Auth::user()->widgets()->findOrFail($id);
        $settings = $widget->settings ?: Widget::getSettings();

        return view('widget.edit', compact('widget', 'settings'));
    }


    function create()
    {
        $widget = Widget::create([
            'status' => 1,
            'access_key' => bin2hex(random_bytes(32)),
            'user_id' => Auth::user()->id,
            'settings' => '',
        ]);

        return redirect()->route('widgets.show', $widget->id);
    }


    function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'type' => 'string|in:all,my,has_system',
            'country_id' => 'required|exists:countries,id',
            'show_machines_list' => 'string|in:show,disable',
            'locale' => 'string|in:' . implode(',', Option::$systemLocales),
        ]);

        $errors = $validator
            ->errors()
            ->getMessages();


        if ($errors) return redirect()->back();

        $widget = Auth::user()->widgets()->findOrFail($id);
        $region = Region::find($request->region);
        if ($region) {
            $city = $region->cities()->find($request->city_id);
        }
        $current = [];

        foreach (Widget::getSettings() as $option => $setting) {
            $current[$option] = ($request->filled($option)) ? $request->$option : $setting;
        }

        $widget->update([
            'country_id' => $request->country_id,
            'locale' => $request->input('locale'),
            'region_id' => $region->id ?? 0,
            'city_id' => $city->id ?? 0,
            'settings' => json_encode($current),
            'name' => $request->name,
            'type' => Widget::status($request->type),
            'show_machines_list' => Widget::show_list($request->show_machines_list),
        ]);

        return redirect()->back();
    }

    function destroy($id)
    {
        $widget = Auth::user()->widgets()->findOrFail($id);

        if($widget->widget_proposals->isEmpty()){
            $widget->key_history()->delete();
            $widget->forceDelete();
        }


        return redirect()->route('home_widget');
    }

    function withdrawalRequest(Request $request)
    {
        $data = $request->all();

        $validator = Validator::make($data, FinanceTransaction::getRequiredFields($data));

        $errors = $validator
            ->errors()
            ->getMessages();


        if ($errors) return response()->json($errors, 419);

        $sum = round(str_replace(',', '.', $data['sum']) * 100);
        if ($request['type'] === 'out' && ($sum > Auth::user()->getBalance('widget'))) {
            $errors['modals'] = ['Ошибка. Сумма превышает баланс'];
        }
        if ($errors) return response()->json($errors, 419);
        DB::beginTransaction();
        FinanceTransaction::create([
            'user_id' => Auth::user()->id,
            'type' => FinanceTransaction::type($data['type']),
            'sum' => $sum,
            'balance_type' => 2,
            /*'requisites_id' => $requisite->id,
            'requisites_type' => Auth::user()->getActiveRequisiteType(),*/
        ]);

        $old = Auth::user()->getBalance('widget');
        $billing = 'widget';

        Auth::user()->decrementWidgetBalance($sum);

        User\BalanceHistory::create([
            'user_id' => Auth::user()->id,
            'admin_id' => 0,
            'old_sum' => $old,
            'new_sum' => Auth::user()->getBalance('widget'),
            'type' => User\BalanceHistory::getTypeKey('reserve'),
            /*     'requisite_id' => $requisite->id,
                 'requisite_type' => Auth::user()->getActiveRequisiteType(),*/
            'sum' => $sum,
            'reason' => TransactionType::getTypeLng('reserve') . ' Вывод денег со счета.',
            'billing_type' => $billing,
        ]);

        DB::commit();

    }


    function generateWidget(Request $request, $key)
    {
        $request->merge(['key' => $key]);

        $widget = Widget::whereAccessKey($request->key)->first();
        if ($widget) {

            App::setLocale($widget->locale);
            $widget_h = WidgetRequestHistory::firstOrCreate([
                'widget_key' => $request->key,
                'referer' => $request->url,
                'widget_id' => $widget->id,
            ]);
            $widget_h->increment('success');
        } else {
            $widget_h = WidgetRequestHistory::firstOrCreate([
                'widget_key' => $request->key,
                'referer' => $request->url,
                'widget_id' => 0,
            ]);
            $widget_h->increment('fail');
            return response()->json([]);
        }
        $machines = Machinery::with('user');
        $user = null;


        switch ($widget->type) {
            case Widget::status('all'):
                $regions = $widget->country->regions;
                $types = Type::all();
                break;
            case Widget::status('my'):

                $regions = $widget->country->regions()->whereHas('machines', function ($q) use ($widget) {
                    $q->whereUserId($widget->user->id);
                })->get();
                $types = Type::whereHas('machines', function ($q) use ($widget) {
                    $q->whereUserId($widget->user->id);
                })->get();
                $user = $widget->user;
                $machines = $widget->user->machines();
                break;

            case Widget::status('has_system'):
                $regions = $widget->country->regions()->whereHas('machines')->get();
                $types = Type::whereHas('machines')->get();
                break;
        }
        if ($request->filled('type_id')) {
            $machines->whereType($request->type_id);
        }
        if ($request->filled('region')) {
            $machines->whereRegionId($request->region);
        }
        if ($request->filled('city_id')) {
            $machines->whereCityId($request->city_id);
        }
        if ($widget->type === Widget::status('my')) {

            $machines->whereUserId($widget->user_id);
        }

        $settings = $widget->settings ?: Widget::getSettings();

        if($widget->show_machines_list === \App\Service\Widget::SHOW_LIST('show'))
        {
            $paginate_count = (int) ($settings['x_column'] * $settings['y_column']);

        }

        $boot_columns =  round(12 / ($settings['y_column'] > 12 ? 12 : $settings['y_column']));

        $machines = $machines->paginate($paginate_count ?? 10);
        $time_type = Widget::$time_type;


        $initial_type_filter = $request->filled('type_id') ? Type::find($request->type_id) : '';
        $initial_region_filter = ($request->filled('region') ? Region::find($request->region) : '');
        $checked_city_source_filter = ($request->filled('city_id') ? City::find($request->city_id) : '');

        return view('widget.widget', compact(
            'widget',
            'regions',
            'machines', 'types',
            'user', 'time_type', 'initial_region_filter', 'initial_type_filter', 'checked_city_source_filter', 'boot_columns', 'settings'
        ));
    }

    function searchMachines(Request $request)
    {
        $widget = Widget::whereAccessKey($request->key)->firstOrFail();
        $machines = Machinery::with('user');

        if ($request->filled('type_id')) {
            $machines->whereType($request->type_id);
        }
        if ($request->filled('region')) {
            $machines->whereRegionId($request->region);
        }
        if ($request->filled('city_id')) {
            $machines->whereCityId($request->city_id);
        }
        if ($widget->type === Widget::status('my')) {

            $machines->whereUserId($widget->user_id);
        }

        $machines = $machines->paginate(10);

        return response()->json(['view' => view('widget.machines_list', compact('machines'))]);
    }


    function registerWidgetUser(Request $request)
    {
        if ($request->has('phone')) {
            $request->merge(
                ['phone' => (int)str_replace(
                    [')', '(', ' ', '+', '-'],
                    '',
                    $request->input('phone'))
                ]);
        }
        $validator = $this->validator($request->all());
        $validator->fails();
        $rules = $validator->failed();

        $errors = $validator
            ->errors()
            ->getMessages();

        if (isset($rules['email']['Unique']) || isset($rules['phone']['Unique'])) {
            $errors['modals'] = [trans('transbaza_widgets.already_user')
            ];
        }
        if ($errors) return response()->json($errors, 400);

        DB::transaction(function () use ($request) {
            $user = User::create([
                'email' => $request->input('email'),
                'phone' => $request->input('phone'),
                'password' => Hash::make($request->input('password')),
            ]);

            $role = Role::where('alias', 'widget')
                ->firstOrFail();
            $user->roles()->attach($role->id);

            //$user->sendToken();
        });


        return response()->json([
            'message' => trans('auth.register_success')
        ], 200);
    }
}
