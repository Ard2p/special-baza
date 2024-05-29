<?php

namespace Modules\CompanyOffice\Entities\Company;

use App\User;
use App\Overrides\Model;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Modules\AdminOffice\Entities\Marketing\Mailing\Template;
use Modules\CompanyOffice\Services\BelongsToCompanyBranch;
use Modules\Dispatcher\Entities\Customer;
use Modules\RestApi\Emails\DBMail;

class EmployeeInvite extends Model
{
    use BelongsToCompanyBranch;

    protected $table = 'company_employee_invites';

    protected $fillable = [
        'email',
        'hash',
        'role',
        'company_branch_id'
    ];


    function user()
    {
        return $this->belongsTo(User::class, 'email', 'email');
    }

    static function sendInvite($email, $role, $branch_id)
    {

        $invite = self::create([
            'email' => $email,
            'role' => $role,
            'hash' => Str::random(10),
            'company_branch_id' => $branch_id
        ]);

        $invite->send();
    }

    function send()
    {

        $template = Template::getTemplate($this->isCustomerInvite()
            ? Template::TYPE_INVITE_CUSTOMER_USER
            : Template::TYPE_INVITE_EMPLOYEE,
            $this->company_branch->company->domain_id);

        Mail::to($this->email)->queue(new DBMail($template, [
            'company' => $this->company_branch->name,
            'link' => route('invite_employee', [
                $this->company_branch_id,
                'hash' => $this->hash
            ])
        ]));

    }

    function isCustomerInvite()
    {
        return Str::contains($this->role, 'customer');
    }

    function getCustomerAttribute()
    {
        if($this->isCustomerInvite()) {
            $id = explode('_', $this->role)[1];
            return Customer::find($id);
        }

        return null;
    }

}
