<?php

namespace Modules\CompanyOffice\Http\Controllers;

use App\Service\RequestBranch;
use App\User;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Modules\CompanyOffice\Entities\Company;
use Modules\CompanyOffice\Entities\Company\CompanyBranch;
use Modules\CompanyOffice\Entities\Company\Contact;
use Modules\CompanyOffice\Entities\Company\EmployeeInvite;
use Modules\CompanyOffice\Entities\CompanyTag;
use Modules\CompanyOffice\Http\Requests\BranchSettingsRequest;
use Modules\CompanyOffice\Services\CompaniesService;
use Modules\CompanyOffice\Services\CompanyRoles;
use Modules\CompanyOffice\Services\ContactsService;
use Modules\CompanyOffice\Services\InviteEmployeeService;

class BranchesController extends Controller
{
    private $currentBranch;

    public function __construct(Request $request, RequestBranch $companyBranch)
    {
        $this->currentBranch = $companyBranch->companyBranch;
        if ($request->filled('phone')) {
            $request->merge([
                'phone' => trimPhone($request->input('phone'))
            ]);
        }
        if ($request->filled('contacts')) {
            $contacts = $request->input('contacts');
            foreach ($contacts as &$contact) {
                $contact['phone'] = trimPhone($contact['phone'] ?? '');
            }
            $request->merge([
                'contacts' => $contacts
            ]);
        }

        if (!\App::runningInConsole() && !Str::contains($request->route()->getActionName(), ['inviteUser', 'acceptInvite', 'inviteInfo'])) {

            $block = $this->currentBranch->getBlockName(CompanyRoles::BRANCH_DASHBOARD);
            $this->middleware("accessCheck:{$block}," . CompanyRoles::ACTION_SHOW)->only(
                [
                    'generateInvite',
                    'inviteUser',
                    'inviteInfo',
                    'updateBranch',
                    'getEmployees',
                ]);
            $this->middleware("accessCheck:{$block}," . CompanyRoles::ACTION_UPDATE)->only(
                [
                    'setSettings',
                    'getSettings',
                ]);
        }

    }

    function getEmployees()
    {

        return Cache::remember("{$this->currentBranch->id}_employees", 999, function () {
            return $this->currentBranch->employees()->with('contacts')->get()->toArray();
        });
    }

    function getBranch(Request $request, $id)
    {
        $branch = CompanyBranch::with('employees', 'region', 'city', 'contacts','ins_setting','ins_tariff_settings','client_bank_settings')->userHasAccess(null, '*')->findOrFail($id);

        return $branch;
    }

    function updateBranch(Request $request)
    {
        $nameRules = [
            'name' => 'required|min:5|max:255',
            'region_id' => 'required|exists:regions,id',
            'city_id' => [
                'required',
                Rule::exists('cities', 'id')->where('region_id', $request->region_id ?: 0)
            ],
        ];

        $contactRules = ContactsService::getValidationRules();
        $request->validate($request->hasAny([
            'name',
            'region_id',
            'city_id',
        ]) ? $nameRules : $contactRules, [], ContactsService::getValidationAttributes());

        DB::beginTransaction();

        if ($request->hasAny([
            'name',
            'region_id',
            'city_id',
            'avito_partner',
            'commission',
            'is_not_rf',
            'invoice_pay_days_count',
        ])) {
            $this->currentBranch->update([
                'name' => $request->input('name'),
                'region_id' => $request->input('region_id'),
                'invoice_pay_days_count' => $request->input('invoice_pay_days_count'),
                'auto_prolongation' => $request->input('auto_prolongation'),
                'avito_partner' => $request->input('avito_partner'),
                'commission' => $request->input('commission'),
                'support_link' => $request->input('support_link'),
                'is_not_rf' => $request->input('is_not_rf'),
                'currency_code' => $request->input('currency_code'),
                'city_id' => $request->input('city_id'),
            ]);
        } else {
            $this->currentBranch->addContacts($request->input('contacts'));
        }

        DB::commit();


        return response()->json([]);

    }

    function getLegalRequisites()
    {

        return $this->currentBranch->legal_requisistes;
    }

    function getIndividualRequisites()
    {
        return $this->currentBranch->individual_requisistes;
    }

    function getBranchRoles()
    {
        $roles = [];

        foreach (array_keys(CompanyRoles::getBranchRoles()) as $role) {

            $roles[] = [
                'name' => trans('transbaza_roles.' . $role),
                'value' => $role,
            ];
        }

        return response()->json($roles);

    }


    function generateInvite(Request $request, $branch_id)
    {
        $request->validate([
            'email' => 'required|email',
            'role' => 'required|in:' . implode(',', array_keys(CompanyRoles::getBranchRoles()))
        ]);
        $branch = CompanyBranch::query()->userHasAccess()->findOrFail($branch_id);

        (new InviteEmployeeService())
            ->setCompanyBranch($branch)
            ->generateInvite($request->input('email'), $request->input('role'));


        return response()->json();
    }

    function inviteUser(Request $request, $branch_id)
    {
        $invite = EmployeeInvite::query()
            ->where('hash', $request->input('hash'))
            ->where('company_branch_id', $branch_id)
            ->firstOrFail();

        return redirect()->away($invite->company_branch->getUrl('accept-invite', ['hash' => $invite->hash]));
    }

    function changeEmployee(Request $request, $branch_id)
    {
        $request->validate([
            'id' => 'required|exists:users,id',
            'role' => 'required|in:' . implode(',', array_keys(CompanyRoles::getBranchRoles())),
            'machinery_base_id' => 'nullable|exists:machinery_bases,id',
            'sms_notify' => 'nullable|boolean',
        ]);
        /** @var CompanyBranch $branch */
        $branch = CompanyBranch::query()->userHasAccess()->findOrFail($branch_id);

        $employee = $branch->employees()->findOrFail($request->input('id'));

      /*  if ($employee->id === Auth::id()) {
            return response()->json([
                'role' => [
                    'Невозможно изменить роль администратора.'
                ]
            ], 400);
        }*/
        DB::beginTransaction();
        $service = (new InviteEmployeeService())
            ->setCompanyBranch($this->currentBranch)
            ->changeRole($employee, $request->input('role'), $request->input('machinery_base_id'));
        $employee->update([
            'sms_notify' => $request->input('sms_notify')
        ]);
        DB::commit();

        return response()->json();

    }

    function detachEmployee(Request $request, $branch_id, $employee_id)
    {
        $branch = CompanyBranch::query()->userHasAccess()->findOrFail($branch_id);
        $employee = $branch->employees()->findOrFail($employee_id);
        DB::beginTransaction();
        (new InviteEmployeeService())
            ->setCompanyBranch($this->currentBranch)
            ->detachEmployee($employee);
        DB::commit();

        return response()->json();
    }

    function inviteInfo(Request $request)
    {
        $request->validate([
            'hash' => 'required|string'
        ]);

        $info = (new InviteEmployeeService())
            ->setCompanyBranch($this->currentBranch)
            ->inviteInfo($request->input('hash'));

        return response()->json($info);

    }

    function acceptInvite(Request $request)
    {
        /** @var EmployeeInvite $invite */
        $invite = EmployeeInvite::query()
            ->where('hash', $request->input('hash'))
            ->where('company_branch_id', $this->currentBranch->id)
            ->firstOrFail();

        $service = (new InviteEmployeeService())
            ->setCompanyBranch($this->currentBranch);

        $info = $service->inviteInfo($request->input('hash'));

        $rules = [
            'password' => 'required|string|min:6|max:25'
        ];

        if (!$info['user_exists']) {
            $rules['phone'] = 'required|numeric|digits:' . $this->currentBranch->company->domain->options['phone_digits'] . '|unique:users,phone';
            $rules['password'] .= '|confirmed';
            $rules['contact_person'] = 'required|string|min:3|max:255';
        }
        $request->validate($rules);

        DB::beginTransaction();

        $user = $service->acceptInvite(
            $invite,
            $request->input('password'),
            $request->input('phone'),
            $request->input('contact_person'));

        DB::commit();

        $token = $user->createToken('Token from Hash')->accessToken;
        return response()->json([
            'token' => $token,
            'user' => \Modules\RestApi\Transformers\User::make($user),
        ]);

    }

    /**
     * Редактирование настроек филиала.
     * @param BranchSettingsRequest $request
     * @param $id
     * @return \Illuminate\Http\JsonResponse
     */
    function setSettings(BranchSettingsRequest $request, $id)
    {

        $branch = CompanyBranch::query()->userHasAccess()->findOrFail($id);

        $service = new CompaniesService($branch->company);

        switch ($request->input('type')) {
            case 'default_contract':
                $service->setBranchContractSettings($branch->id, $request->all());
                break;
        }

        return response()->json();

    }

    function getSettings($branchId)
    {
        $branch = CompanyBranch::query()->userHasAccess()->findOrFail($branchId);

        return response()->json($branch->getSettings());
    }

    function getTags(Request $request)
    {
        if($request->filled('paginate'))
            return $this->currentBranch->tags()->paginate($request->perPage ?: 15);

        return $this->currentBranch->tags()->pluck('name');
    }


    function storeTag(Request $request, $id)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'color' => 'nullable|string|max:255',
        ]);
        $this->currentBranch->tags()->save(new CompanyTag(['name' => $request->input('name'), 'color' => $request->input('color')]));
        return response()->json();
    }

    function editTag(Request $request, $id, $tagId)
    {
        $request->validate([
            'name' => 'required|string|max:255'
        ]);
        $tag = $this->currentBranch->tags()->findOrFail($tagId);

        $tag->update([
            'name' => $request->input('name')
        ]);

        return response()->json();
    }

    function deleteTag($id, $tagId)
    {
        $this->currentBranch->tags()->findOrFail($tagId)->delete();

        return response()->json();
    }
}
