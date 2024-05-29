<?php


namespace Modules\CompanyOffice\Services;


use App\User;
use Spatie\Permission\Models\Permission;

class CompanyRoles
{
    const BRANCH_PAYMENTS = 'branch_payments';
    const BRANCH_CLIENTS = 'branch_clients';
    const BRANCH_CONTRACTORS = 'branch_contractors';
    const BRANCH_VEHICLES = 'branch_vehicles';
    const BRANCH_CALENDAR = 'branch_calendar';
    const BRANCH_TELEPHONY = 'branch_telephony';
    const BRANCH_PROPOSALS = 'branch_proposals';
    const BRANCH_ORDERS = 'branch_orders';
    const BRANCH_DASHBOARD = 'branch_dashboard';


    const ROLE_MANAGER = 'manager';
    const ROLE_HEAD = 'head';
    const ROLE_MECHANIC = 'mechanic';
    const ROLE_DIRECTOR = 'director';
    const ROLE_ADMIN = 'admin';
    const ROLE_REGIONAL_MANAGER = 'regional_manager';
    const ROLE_RENT_MANAGER = 'rent_manager';
    const ROLE_SALE_MANAGER = 'sale_manager';
    const ROLE_RENTAL_POINT_DIRECTOR = 'rental_point_director';
    const ROLE_EXECUTIVE_DIRECTOR = 'executive_director';
    const ROLE_COMMERCIAL_DIRECTOR = 'commercial_director';
    const ROLE_SERVICE_DIRECTOR = 'service_director';
    const ROLE_ACCOUNTANT = 'accountant';
    const ROLE_COMPANY_DIRECTOR = 'company_director';
    const ROLE_ADMINISTRATOR = 'administrator';
    const ROLE_DISPATCHER = 'dispatcher';

    const ACTION_SHOW = 'show';
    const ACTION_CREATE = 'create';
    const ACTION_UPDATE = 'update';
    const ACTION_DELETE = 'delete';

    const ACTIONS = [
        'show',
        'create',
        'update',
        'delete',
    ];

    static function getActions()
    {
        return [
            self::ACTION_SHOW,
            self::ACTION_CREATE,
            self::ACTION_UPDATE,
            self::ACTION_DELETE,
        ];
    }

    static function getBranchBlocks()
    {
        return [
            self::BRANCH_PAYMENTS,
            self::BRANCH_CLIENTS,
            self::BRANCH_CONTRACTORS,
            self::BRANCH_VEHICLES,
            self::BRANCH_CALENDAR,
            self::BRANCH_TELEPHONY,
            self::BRANCH_PROPOSALS,
            self::BRANCH_ORDERS,
            self::BRANCH_DASHBOARD,
        ];
    }

    /**
     * Роли для филиала с определенными доступами.
     * @return array
     */
    static function getBranchRoles()
    {
        $access = array_map(function ($val) {
            return $val.'.'.implode(',', self::getActions());
        }, self::getBranchBlocks());
        return [
            /** Роль менеджера  */
//            self::ROLE_MANAGER => [
//                self::formAccessString(self::BRANCH_PAYMENTS, [self::ACTION_SHOW, self::ACTION_CREATE, self::ACTION_UPDATE, self::ACTION_DELETE,]),
//                self::formAccessString(self::BRANCH_CLIENTS, [self::ACTION_SHOW, self::ACTION_CREATE, self::ACTION_UPDATE, self::ACTION_DELETE,]),
//                self::formAccessString(self::BRANCH_CONTRACTORS, [self::ACTION_SHOW,]),
//                self::formAccessString(self::BRANCH_VEHICLES, [self::ACTION_SHOW,]),
//                self::formAccessString(self::BRANCH_CALENDAR, [self::ACTION_SHOW, self::ACTION_CREATE, self::ACTION_UPDATE, self::ACTION_DELETE,]),
//                self::formAccessString(self::BRANCH_TELEPHONY, [self::ACTION_SHOW, self::ACTION_CREATE, self::ACTION_UPDATE, self::ACTION_DELETE,]),
//                self::formAccessString(self::BRANCH_PROPOSALS, [self::ACTION_SHOW, self::ACTION_CREATE, self::ACTION_UPDATE, self::ACTION_DELETE,]),
//                self::formAccessString(self::BRANCH_ORDERS, [self::ACTION_SHOW, self::ACTION_CREATE, self::ACTION_UPDATE, self::ACTION_DELETE,]),
//                self::formAccessString(self::BRANCH_DASHBOARD, [self::ACTION_SHOW,]),
//            ],
//            /** Роль Руководитель  */
//            self::ROLE_HEAD => [
//                self::formAccessString(self::BRANCH_PAYMENTS, [self::ACTION_SHOW, self::ACTION_CREATE, self::ACTION_UPDATE, self::ACTION_DELETE,]),
//                self::formAccessString(self::BRANCH_CLIENTS, [self::ACTION_SHOW, self::ACTION_CREATE, self::ACTION_UPDATE, self::ACTION_DELETE,]),
//                self::formAccessString(self::BRANCH_CONTRACTORS, [self::ACTION_SHOW, self::ACTION_CREATE, self::ACTION_UPDATE, self::ACTION_DELETE,]),
//                self::formAccessString(self::BRANCH_VEHICLES, [self::ACTION_SHOW,]),
//                self::formAccessString(self::BRANCH_CALENDAR, [self::ACTION_SHOW, self::ACTION_CREATE, self::ACTION_UPDATE, self::ACTION_DELETE,]),
//                self::formAccessString(self::BRANCH_TELEPHONY, [self::ACTION_SHOW, self::ACTION_CREATE, self::ACTION_UPDATE, self::ACTION_DELETE,]),
//                self::formAccessString(self::BRANCH_PROPOSALS, [self::ACTION_SHOW, self::ACTION_CREATE, self::ACTION_UPDATE, self::ACTION_DELETE,]),
//                self::formAccessString(self::BRANCH_ORDERS, [self::ACTION_SHOW, self::ACTION_CREATE, self::ACTION_UPDATE, self::ACTION_DELETE,]),
//                self::formAccessString(self::BRANCH_DASHBOARD, [self::ACTION_SHOW,]),
//            ],
//            /** Роль Бухгалтер  */
//            self::ROLE_ACCOUNTANT => [
//                self::formAccessString(self::BRANCH_CLIENTS, [self::ACTION_SHOW]),
//                self::formAccessString(self::BRANCH_CONTRACTORS, [self::ACTION_SHOW]),
//                self::formAccessString(self::BRANCH_VEHICLES, [self::ACTION_SHOW]),
//                self::formAccessString(self::BRANCH_CALENDAR, [self::ACTION_SHOW]),
//                self::formAccessString(self::BRANCH_TELEPHONY, [self::ACTION_SHOW]),
//                self::formAccessString(self::BRANCH_PROPOSALS, [self::ACTION_SHOW]),
//                self::formAccessString(self::BRANCH_ORDERS, [self::ACTION_SHOW]),
//                self::formAccessString(self::BRANCH_PAYMENTS),
//                self::formAccessString(self::BRANCH_DASHBOARD, [self::ACTION_SHOW,]),
//            ],
//            /** Роль Механик  */
//            self::ROLE_MECHANIC => [
//                self::formAccessString(self::BRANCH_VEHICLES, [self::ACTION_SHOW]),
//                self::formAccessString(self::BRANCH_CALENDAR, [self::ACTION_SHOW]),
//                self::formAccessString(self::BRANCH_DASHBOARD, [self::ACTION_SHOW,]),
//            ],
//            /** Роль Директор  */
//            self::ROLE_DIRECTOR => [
//                self::formAccessString(self::BRANCH_PAYMENTS, [self::ACTION_SHOW]),
//                self::formAccessString(self::BRANCH_CLIENTS, [self::ACTION_SHOW]),
//                self::formAccessString(self::BRANCH_CONTRACTORS, [self::ACTION_SHOW]),
//                self::formAccessString(self::BRANCH_VEHICLES, [self::ACTION_SHOW]),
//                self::formAccessString(self::BRANCH_CALENDAR, [self::ACTION_SHOW]),
//                self::formAccessString(self::BRANCH_TELEPHONY, [self::ACTION_SHOW]),
//                self::formAccessString(self::BRANCH_PROPOSALS, [self::ACTION_SHOW]),
//                self::formAccessString(self::BRANCH_ORDERS, [self::ACTION_SHOW]),
//                self::formAccessString(self::BRANCH_DASHBOARD, [self::ACTION_SHOW,]),
//            ],
//            /** Роль Администратор  */
//            self::ROLE_ADMIN => array_map(function ($val) {
//                return $val . '.' . implode(',' , self::getActions());
//            }, self::getBranchBlocks()),

            self::ROLE_REGIONAL_MANAGER => $access,
            self::ROLE_RENT_MANAGER => $access,
            self::ROLE_SALE_MANAGER => $access,
            self::ROLE_RENTAL_POINT_DIRECTOR => $access,
            self::ROLE_EXECUTIVE_DIRECTOR => $access,
            self::ROLE_COMMERCIAL_DIRECTOR => $access,
            self::ROLE_SERVICE_DIRECTOR => $access,
            self::ROLE_ACCOUNTANT => $access,
            self::ROLE_COMPANY_DIRECTOR => $access,
            self::ROLE_ADMINISTRATOR => $access,
            self::ROLE_DISPATCHER => $access,
        ];
    }

    static function formAccessString($block, array $actions = [])
    {
        return $block.($actions ? '.'.trim(implode(',', $actions), ',') : '');
    }

    static function syncRoleWithPermissions(User $user, $branch_id, $role)
    {
        self::syncRoles($user, $branch_id, 'branch', $role);
    }

    private static function syncRoles(User $user, $id, $type, $role)
    {
        $permissions = self::getBranchRoles();

        $user->revokePermissionTo($user->permissions()->where('name', 'like', "{$id}_{$type}%")->get());

        foreach ($permissions[$role] as $block) {
            $blockName = str_replace('branch', $type, $block);
            $permission_name = "{$id}_{$blockName}";
            $permission = Permission::findOrCreate($permission_name, 'api');

            $user->givePermissionTo($permission);
        }
    }

    static function syncCompanyRoleWithPermission(User $user, $companyId, $role)
    {
        self::syncRoles($user, $companyId, 'company', $role);
    }
}
