<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class RolesSeeder extends Seeder
{

    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $roles = [
            [
                'name' => 'rent_manager',
                'guard_name' => 'api'
            ],
            [
                'name' => 'sale_manager',
                'guard_name' => 'api'
            ],
            [
                'name' => 'rental_point_director',
                'guard_name' => 'api'
            ],
            [
                'name' => 'executive_director',
                'guard_name' => 'api'
            ],
            [
                'name' => 'commercial_director',
                'guard_name' => 'api'
            ],
            [
                'name' => 'service_director',
                'guard_name' => 'api'
            ],
            [
                'name' => 'accountant',
                'guard_name' => 'api'
            ],
            [
                'name' => 'company_director',
                'guard_name' => 'api'
            ],
            [
                'name' => 'administrator',
                'guard_name' => 'api'
            ],
            [
                'name' => 'dispatcher',
                'guard_name' => 'api'
            ],
        ];

        foreach ($roles as $role) {
            Role::query()->updateOrCreate(['name' => $role['name']], $role);
        }
    }
}
