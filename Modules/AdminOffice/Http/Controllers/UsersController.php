<?php

namespace Modules\AdminOffice\Http\Controllers;

use App\Finance\FinanceTransaction;
use App\Helpers\RequestHelper;
use App\System\Audit;
use App\System\DeleteUserLog;
use App\User;
use App\User\BalanceHistory;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Modules\AdminOffice\Entities\DownloadLink;
use Modules\AdminOffice\Entities\Filter;
use Modules\AdminOffice\Entities\YandexPhoneCredential;
use Modules\AdminOffice\Services\RoleService;
use Modules\AdminOffice\Transformers\EditUser;
use Modules\CompanyOffice\Services\CompanyRoles;
use Modules\CorpCustomer\Entities\CorpBank;
use Modules\CorpCustomer\Entities\CorpBrand;
use Modules\RestApi\Entities\Domain;
use Rap2hpoutre\FastExcel\FastExcel;

class UsersController extends Controller
{


    public function __construct(Request $request)
    {


        $this->middleware('accessCheck:' . RoleService::ADMIN_USERS )->only('getUsers', 'getCalls', 'getContractorBalance', 'getContractorTransactions');
        $this->middleware('accessCheck:' . RoleService::ADMIN_USERS )->only('updateUser', 'createUser', 'storeYaCall', 'changePassword', 'connectModule');
        $this->middleware('accessCheck:' . RoleService::ADMIN_USERS )->only('totalDelete');

        if ($request->filled('phone')) {
            $request->merge([
                'phone' => trimPhone($request->input('phone'))
            ]);
        }
    }

    private function modifyQuery(Request $request, $users)
    {

        $users->forDomain();
        $filter = new Filter($users);

        $filter->getLike([
            'email' => 'email',
            'phone' => 'phone',
        ]);
        $filter->getEqual([
            'city' => 'native_city_id',
            'region' => 'native_region_id',
            'regional_representative_id' => 'regional_representative_id',
        ]);
        $filter->getEqual([
            'passed_moderation' => 'passed_moderation',
            'order_management' => 'order_management',
        ], true);

        $filter->getDateBetween([
            'created_from' => 'created_at',
            'created_to' => 'created_at',
        ]);
        if (Auth::check()) {
            if (Auth::user()->isRegionalRepresentative() && !Auth::user()->isSuperAdmin()) {
                $users->forRegionalRepresentative();
            }

            if (!Auth::user()->isSuperAdmin()) {
                $users->whereDoesntHave('roles', function ($q) {
                    $q->whereIn('alias', ['admin', 'content_admin']);
                });
            }
        }

        if ($request->filled('role')) {

            $users->whereHas('roles', function ($q) use ($request) {
                $q->where('roles.id', $request->input('role'));
            });

        }
    }

    function searchUser(Request $request)
    {
        $users = User::query();

       $users->where('email', 'like', "%{$request->input('search')}%")
           ->orWhere('id', 'like', "%{$request->input('search')}%");

       return $users->take(20)->get();

    }

    function getUsers(Request $request, $id = null)
    {

        $users = User::with('region', 'city', 'roles', 'contractor_balance', 'regional_representative', 'adminDomainAccess', 'branches');

        $this->modifyQuery($request, $users);

        if ($id) {
            $user = $users->findOrFail($id);
            return $request->filled('info') ? $user : EditUser::make($user);
        }


        $users = $users->orderBy('created_at', 'DESC')->paginate($request->per_page ?: 10);

        $users->map(function ($user) {

            if ($user->regional_representative) {
                $user->regional_representative->setAppends(['rp_name']);
            }
            return $user;
        });
        return $users;
    }

    function excel(Request $request)
    {
        $link = DownloadLink::whereLinkHash($request->link_hash)->firstOrFail();
        $link->delete();

        if ($link->user && !Auth::check()) {
            Auth::onceUsingId($link->user_id);
        }

        $users = User::with('region', 'city', 'roles');
        $this->modifyQuery($request, $users);
        $users = $users->get();

        return (new FastExcel($users))->download('Users.xlsx', function ($user) {
            return [
                'Пользователь' => $user->email,
                'ID' => $user->id,
                'Телефон' => $user->phone,
                'РП' => $user->regional_representative->email ?? '',
                'Регион' => $user->region->name ?? '',
                'Город' => $user->city->name ?? '',
                'Роль' => implode(', ', $user->roles->pluck('name')->toArray()),
                'Дата регистрации' => $user->created_at->format('d.m.Y H:i'),
                'Дата Последней активности' => $user->last_activity->format('d.m.Y H:i'),
                'Есть техника' => $user->machines->isNotEmpty() ? 'Да' : 'Нет',
            ];
        });
    }


    function updateUser(Request $request, $id)
    {
        $user = User::findOrFail($id);

        $errors = Validator::make($request->all(), [
            'email' => 'required|email|unique:users,email,' . $id,
            'contact_person' => 'nullable|string|max:255',
            'phone' => 'required|numeric|digits:' . RequestHelper::requestDomain()->options['phone_digits'] . '|unique:users,phone,' . $id,
            'email_confirm' => 'nullable',
            'phone_confirm' => 'nullable',
            'native_region_id' => 'required|exists:regions,id',
            'country_id' => 'required|exists:countries,id',
            'native_city_id' => 'required|exists:cities,id',
            'regional_representative_id' => 'nullable|integer',
            'contractor_alias' => 'required|string|unique:users,contractor_alias,' . $id,
            'roles' => 'required|array',
            'domains' => 'nullable|array'
        ])->errors()->getMessages();

        if ($errors) {
            return response()->json($errors, 400);
        }

        DB::beginTransaction();

        $user->update([
            'email' => $request->input('email'),
            'phone' => $request->input('phone'),
            'email_confirm' => toBool($request->input('email_confirm')),
            'phone_confirm' => toBool($request->input('phone_confirm')),
            'order_management' => toBool($request->input('order_management')),
            'is_regional_representative' => filter_var($request->input('is_regional_representative'), FILTER_VALIDATE_BOOLEAN),
            'native_region_id' => $request->input('native_region_id'),
            'country_id' => $request->input('country_id'),
            'native_city_id' => $request->input('native_city_id'),
            'regional_representative_id' => $request->input('regional_representative_id', 0),
            'contractor_alias' => $request->input('contractor_alias'),
            'contact_person' => $request->input('contact_person'),
            'contractor_alias_enable' => true,
            'passed_moderation' => toBool($request->input('passed_moderation')),
        ]);


        $roles = \Spatie\Permission\Models\Role::whereIn('id', $request->input('roles'))->get();

        $domains = Domain::whereIn('id', $request->input('domains'))->get();

        $user->roles()->sync($roles->pluck('id'));

        $user->adminDomainAccess()->sync($domains->pluck('id'));

        DB::commit();

        $user->refresh();
        return response()->json(EditUser::make($user));
    }

    function createUser(Request $request)
    {

        $request->validate([
            'email' => 'required|email|unique:users,email',
            'password' => 'required|min:6|string',
            'contact_person' => 'nullable|string|max:255',
            'phone' => 'required|numeric|digits:' . RequestHelper::requestDomain()->options['phone_digits'] . '|unique:users,phone',
            'email_confirm' => 'nullable',
            'phone_confirm' => 'nullable',
            'native_region_id' => 'required|exists:regions,id',
            'country_id' => 'required|exists:countries,id',
            'native_city_id' => 'required|exists:cities,id',
            'regional_representative_id' => 'nullable|exists:users,id',
            'passed_moderation' => 'nullable|boolean',
            'roles' => 'required|array'
        ]);

        DB::beginTransaction();

        $user = User::create([
            'email' => $request->input('email'),
            'password' => Hash::make($request->input('password')),
            'phone' => $request->input('phone'),
            'email_confirm' => toBool($request->input('email_confirm')),
            'phone_confirm' => toBool($request->input('phone_confirm')),
            'native_region_id' => $request->input('native_region_id'),
            'country_id' => $request->input('country_id'),
            'native_city_id' => $request->input('native_city_id'),
            'regional_representative_id' => Auth::user()->isRegionalRepresentative() ? Auth::user()->id : ($request->regional_representative_id ?: 0),
            'contractor_alias' => $request->input('contractor_alias', ''),
            'contact_person' => $request->input('contact_person'),
            'contractor_alias_enable' => true,
            'is_regional_representative' => toBool($request->input('is_regional_representative')),
            'order_management' => toBool($request->input('order_management')),
            'passed_moderation' => toBool($request->input('passed_moderation')),
        ]);


        config(['tmp_password' => $request->input('password')]);

        $roles = Role::whereIn('id', $request->input('roles'));

        if (!Auth::user()->isSuperAdmin()) {
            $roles->whereIn('alias', ['customer', 'performer']);
        }
        $roles = $roles->get();

        $user->roles()->sync($roles->pluck('id'));


        DB::commit();


        return response()->json($user);

    }

    function changePassword(Request $request, $id)
    {
        $user = User::findOrFail($id);

        $errors = Validator::make($request->all(), [
            'new_password' => 'required|string|min:6|max:20',
        ])->errors()->getMessages();

        if ($errors) {
            return response()->json($errors, 419);
        }

        $user->update([
            'password' => Hash::make($request->input('new_password'))
        ]);

        return response()->json();
    }

    function getContractorTransactions($user_id)
    {

        $transactions = FinanceTransaction::where('user_id', $user_id)
            ->where('balance_type', 1)
            ->orderBy('created_at', 'DESC')
            ->paginate(10);

        return $transactions;

    }

    function getContractorBalance($user_id)
    {

        $balances = BalanceHistory::where('user_id', $user_id)
            ->where('billing_type', 'contractor')
            ->orderBy('created_at', 'DESC')
            ->paginate(10);

        return $balances;
    }

    function totalDelete($id)
    {
        $user = User::withTrashed()->findOrFail($id);

        $fields = [];
        DB::beginTransaction();

        $user->notification_histories()->delete();

        $user->balances()->delete();

        $user->documents()->delete();

        $user->support()->delete();

        $user->balance_history()->delete();

        $user->transactions_history()->delete();


        if ($user->commission) {
            $fields['commission'] = $user->commission->toArray();
            $user->commission->delete();
        }

        $user->forceDelete();


        DB::commit();
        return response()->json(['message' => 'Успешно удален.']);

    }

    function storeYaCall(Request $request)
    {
        $errors = Validator::make($request->all(), [
            'login' => 'required|string|max:255',
            'password' => 'required|string|max:255',
            'user_id' => 'required|exists:users,id'
        ])->errors()->getMessages();

        if ($errors) {
            return response()->json($errors, 400);
        }

        $user = User::findOrFail($request->input('user_id'));

        $account = $user->ya_call ?: new YandexPhoneCredential();

        $account->fill($request->only([
            'login', 'password', 'user_id'
        ]));

        $account->save();

        return response()->json();
    }


    function getActionAudit(Request $request, $id)
    {
        return Audit::where('user_id', $id)->orderBy('created_at', 'desc')->paginate($request->input('per_page', 15));
    }


    function connectModule(Request $request, $user_id)
    {
        $request->validate([
            'module' => 'required|in:dispatcher'
        ]);

        $user = User::findOrFail($user_id);

        if (!$user->connectDispatcherModule()) {
            return response()->json(['errors' => 'Ошибка подключения. Модуль уже подключен'], 400);
        }

        return response()->json([]);
    }


    function attachPredictedCategories(Request $request, $id)
    {
        $user = User::query()->findOrFail($id);

        $request->validate([
            'category_id' => 'required|exists:types,id',
            'count' => 'required|integer|min:1|max:9999',
        ]);

        DB::beginTransaction();

        $user->predicted_categories()->syncWithoutDetaching([
            $request->category_id => [
                'count' => $request->input('count')
            ]
        ]);

        DB::commit();

        return response()->json();

    }

    function detachPredictedCategory(Request $request, $id)
    {
        $user = User::query()->findOrFail($id);

        $request->validate([
            'category_id' => 'required|exists:types,id',
        ]);

        DB::beginTransaction();

        $user->predicted_categories()->detach($request->category_id);

        DB::commit();

        return response()->json();
    }

    function getPredictedCategories($id)
    {
        $user = User::query()->findOrFail($id);

        return $user->predicted_categories;
    }


}
