<?php


namespace Modules\CompanyOffice\Services;


use Modules\Dispatcher\Entities\Customer;
use App\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Modules\CompanyOffice\Entities\Company\CompanyBranch;
use Modules\CompanyOffice\Entities\Company\EmployeeInvite;

class InviteEmployeeService
{
     /** @var CompanyBranch */
    private $companyBranch;

    private $currentInviteInfo;

    /**
     * Приглашение сотрудника в филиал
     * @param string $email
     * @param string $role
     * @return InviteEmployeeService
     */
    function generateInvite($email, $role)
    {

        EmployeeInvite::sendInvite($email, $role, $this->companyBranch->id);

        return  $this;
    }

    /**
     * Информация о приглашении
     * @param $hash
     * @return array
     */
    function inviteInfo($hash)
    {
        /** @var EmployeeInvite $invite */
        $invite = EmployeeInvite::whereHash($hash)
            ->where('company_branch_id', $this->companyBranch->id)
            ->firstOrFail();
        if($invite->isCustomerInvite()) {
            return  $this->getCustomerInviteInfo($invite);
        }
        $employee_exists = $this->companyBranch->employees()
            ->where('users.email', $invite->email)
            ->exists();

        $user_exists = (bool) $invite->user;

        return $this->currentInviteInfo = [
            'company' => $invite->company_branch->name,
            'email' => $invite->email,
            'employee_exists' => $employee_exists,
            'user_exists' => $user_exists,
            'role' => $invite->role,
            'type' => 'employee',
        ];
    }

    private function getCustomerInviteInfo(EmployeeInvite $invite)
    {
        $id = explode('_', $invite->role)[1];
        $customer = Customer::findOrFail($id);
        $user_exists = (bool) $invite->user;
        return $this->currentInviteInfo = [
            'company' =>$invite->company_branch->name,
            'email' => $invite->email,
            'user_exists' => $user_exists,
            'type' => 'customer_user',
            'customer' => $customer,
        ];
    }


    function acceptInvite(EmployeeInvite $invite, $password, $phone = null, $contact_person = null)
    {
       $info = $this->currentInviteInfo ?: $this->inviteInfo($invite->hash);

        /** @var User $user */
        $user = $info['user_exists']
           ? User::query()->whereEmail($invite->email)->first()
           : User::register($invite->email, $phone, $password, $contact_person, $this->companyBranch->domain->country->id);

        if(!Hash::check($password, $user->password)){
            $error =  ValidationException::withMessages([
                'password' => ['Некорректный пароль']
            ]);

            throw $error;
        }
        if($invite->isCustomerInvite()) {

            $info['customer']->corpUsers()->syncWithoutDetaching($user->id);

        }else {
            $this->companyBranch->employees()->syncWithoutDetaching([
                $user->id => [
                    'role' => $invite->role
                ]
            ]);

            CompanyRoles::syncRoleWithPermissions($user, $this->companyBranch->id, $invite->role);
        }


        $invite->delete();

        return $user;
    }

    function changeRole($user, $role, $machineryBaseId = null)
    {
        $this->companyBranch->employees()->syncWithoutDetaching([
            $user->id => [ 
                'role' => $role,
                'machinery_base_id' => $machineryBaseId
            ]
        ]);
        CompanyRoles::syncRoleWithPermissions($user, $this->companyBranch->id, $role);

        return $this;
    }

    function detachEmployee(User $employee)
    {
        $this->companyBranch->employees()->detach([
            $employee->id
        ]);
        $employee->revokePermissionTo(
            $employee->permissions()->where('name', 'like', "{$this->companyBranch->id}_branch%")->get()
        );

        return $this;
    }

    /**
     * @param CompanyBranch $companyBranch
     * @return InviteEmployeeService
     */
    public function setCompanyBranch(CompanyBranch $companyBranch): InviteEmployeeService
    {
        $this->companyBranch = $companyBranch;

        return $this;
    }
}