<?php


namespace Modules\AdminOffice\Services;


use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleService
{
    const SUPER_ADMIN = 'super_admin';
    const ADMIN_DASHBOARD_ACCESS = 'admin_dashboard_access';
    const ADMIN_CONTENT = 'admin_content';
    const ADMIN_VEHICLES = 'admin_vehicles';
    const ADMIN_USERS = 'admin_users';

    const ADMIN_PROPOSALS = 'admin_proposals';
    const ADMIN_SEO_BLOCKS = 'admin_seo_blocks';
    const ADMIN_DIRECTORIES = 'admin_directories';

    const ADMIN_ORDERS = 'admin_orders';
    const ADMIN_CALL_CENTER = 'admin_call_center';
    const ADMIN_SETTINGS = 'admin_settings';

    static function getSystemRoles()
    {
        return [
            [
                'name' => self::SUPER_ADMIN,
            ],
            [
                'name' => 'content-admin',
                'guard_name' => 'api',
                'permissions' => [
                    self::ADMIN_DASHBOARD_ACCESS,
                    self::ADMIN_CONTENT,
                    self::ADMIN_SEO_BLOCKS,
                ]
            ],
            [
                'name' => 'dispatcher',
                'guard_name' => 'api',
            ],
            [
                'name' => 'contractor',
                'guard_name' => 'api',
            ],
            [
                'name' => 'customer',
                'guard_name' => 'api',
            ],
            [
                'name' => 'operator',
                'guard_name' => 'api',
                'permissions' => [
                    self::ADMIN_DASHBOARD_ACCESS,
                    self::ADMIN_PROPOSALS,
                    self::ADMIN_VEHICLES,
                    self::ADMIN_CALL_CENTER,
                    self::ADMIN_USERS,
                ]
            ],
            [
                'name' => 'regional_manager',
                'guard_name' => 'api',
                'permissions' => [
                    self::ADMIN_DASHBOARD_ACCESS,
                    self::ADMIN_VEHICLES,
                    self::ADMIN_ORDERS,
                    self::ADMIN_USERS,
                ]

            ]
        ];
    }

    static function getAdminPermissions()
    {
        return [
          self::ADMIN_DASHBOARD_ACCESS,
          self::ADMIN_CONTENT,
          self::ADMIN_VEHICLES,
          self::ADMIN_USERS,
          self::ADMIN_PROPOSALS,
          self::ADMIN_SEO_BLOCKS,
          self::ADMIN_DIRECTORIES,
          self::ADMIN_ORDERS,
          self::ADMIN_CALL_CENTER,
          self::ADMIN_SETTINGS,
        ];
    }

    static function createRoles()
    {
        DB::beginTransaction();

        foreach (self::getAdminPermissions() as $adminPermission) {
            Permission::findOrCreate($adminPermission, 'api');
        }
        foreach (self::getSystemRoles() as $item) {

            $arr = ['name' => $item['name']];

            if(isset($item['guard_name'])) {
                $arr['guard_name'] = $item['guard_name'];
            }

            $role = Role::findOrCreate($arr['name'], ($arr['guard_name'] ?? null));

            if(!empty($item['permissions'])) {
                $collect = [];

                foreach ($item['permissions'] as $permission) {
                    $permission = Permission::findByName($permission, 'api');

                    $collect[] = $permission;
                }

                $role->syncPermissions($collect);
            }

        }
        DB::commit();
    }
}