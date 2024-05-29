<?php

namespace Modules\Profiles\Database\Seeders;

use Illuminate\Database\Seeder;
use App\Overrides\Model;

class ProfilesDatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Model::unguard();

        // $this->call("OthersTableSeeder");
    }
}
